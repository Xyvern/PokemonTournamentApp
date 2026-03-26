<?php

namespace App\Services;

use App\Models\TournamentMatch;
use App\Models\TournamentEntry;
use App\Models\Tournament;

class TournamentServices
{
    /**
     * Called instantly when a match finishes. Only updates the result code.
     */
    public function setMatchResult($matchId, $resultCode)
    {
        $match = TournamentMatch::find($matchId);
        if ($match && in_array($resultCode, [1, 2, 3])) {
            $match->result_code = $resultCode;
            $match->save();
        }
    }

    /**
     * Called by the Admin when clicking "Generate Next Round" or "Finalize".
     * Calculates Win/Loss/Tie, Points, and Elo for a specific round.
     */
    public function processRoundStats($tournamentId, $roundNumber, EloCalculator $eloCalculator)
    {
        $matches = TournamentMatch::where('tournament_id', $tournamentId)
            ->where('round_number', $roundNumber)
            ->with(['player1.user', 'player2.user']) 
            ->get();

        foreach ($matches as $match) {
            $player1Entry = $match->player1;
            $player2Entry = $match->player2;
            
            $p1User = $player1Entry->user;

            // Handle the "Bye" Match
            if (!$player2Entry) {
                $player1Entry->wins++;
                $player1Entry->points += 3;
                $player1Entry->save();
                
                // Update global stats for a Bye
                $p1User->matches_played++;
                $p1User->matches_won++;
                $p1User->save();
                continue; 
            }

            $p2User = $player2Entry->user;
            $winnerString = '';
            
            if ($match->result_code == 1) $winnerString = 'player1';
            elseif ($match->result_code == 2) $winnerString = 'player2';
            elseif ($match->result_code == 3) $winnerString = 'tie';

            // 1. Calculate the Elo Change
            $eloChange = $eloCalculator->eloChange(43, $p1User->elo, $p2User->elo, $winnerString);

            // 2. Global stats tracking
            $p1User->matches_played++;
            $p2User->matches_played++;

            // 3. Apply Points, Wins, AND Elo
            if ($match->result_code == 1) { 
                // P1 Wins
                $player1Entry->wins++;
                $player1Entry->points += 3;
                $player1Entry->total_elo_gain += $eloChange;
                $p1User->matches_won++;
                $p1User->elo += $eloChange;

                $player2Entry->losses++;
                $player2Entry->total_elo_gain -= $eloChange;
                $p2User->matches_lost++;
                $p2User->elo -= $eloChange;

            } elseif ($match->result_code == 2) { 
                // P2 Wins
                $player2Entry->wins++;
                $player2Entry->points += 3;
                $player2Entry->total_elo_gain += $eloChange;
                $p2User->matches_won++;
                $p2User->elo += $eloChange;

                $player1Entry->losses++;
                $player1Entry->total_elo_gain -= $eloChange;
                $p1User->matches_lost++;
                $p1User->elo -= $eloChange;

            } elseif ($match->result_code == 3) { 
                // Tie
                $player1Entry->ties++;
                $player2Entry->ties++;
                $player1Entry->points += 1;
                $player2Entry->points += 1;
                
                // Assuming your calculator returns a small adjustment for ties
                $p1User->elo += $eloChange;
                $p2User->elo -= $eloChange; 
            }

            // 4. Update the global Elo history (if your calculator tracks a ledger)
            $eloCalculator->updateEloRatings($p1User->elo, $p2User->elo, $winnerString, $match->id);

            // 5. Save the Match's specific elo_gain for historical reference
            $match->elo_gain = $eloChange;
            $match->save();

            // 6. Save Entries
            $player1Entry->save();
            $player2Entry->save();

            // 7. SAVE THE USERS! (This fixes the bug)
            $p1User->save();
            $p2User->save();
        }
    }

    /**
     * Called ONLY when the tournament is finalized.
     * Updates global archetype win rates and play counts.
     */
    public function processArchetypeStats($tournamentId)
    {
        $entries = TournamentEntry::with('deck.globalDeck.archetype')
            ->where('tournament_id', $tournamentId)
            ->get();

        foreach ($entries as $entry) {
            // Only proceed if this deck actually has a registered archetype
            $archetype = $entry->deck->globalDeck->archetype ?? null;
            
            if ($archetype) {
                // Add the total matches played by this user
                $totalMatchesPlayed = $entry->wins + $entry->losses + $entry->ties;
                
                $archetype->increment('times_played', $totalMatchesPlayed);
                $archetype->increment('wins', $entry->wins);
            }
        }
    }

    public function updateStandingsAndTiebreakers($tournamentId)
    {
        $entries = TournamentEntry::where('tournament_id', $tournamentId)->get();
        $matches = TournamentMatch::where('tournament_id', $tournamentId)
            ->whereNotNull('result_code')
            ->get();

        // 1. Build an Opponent History Map and calculate personal Match Win % (MW%)
        $opponents = [];
        $mwPercentages = [];

        foreach ($entries as $entry) {
            $opponents[$entry->id] = [];
            
            $matchesPlayed = $entry->wins + $entry->losses + $entry->ties;
            if ($matchesPlayed == 0) {
                $mwPercentages[$entry->id] = 0.33; // Standard TCG floor
            } else {
                // MW% = Points / (Matches Played * 3). Standard TCG rules floor this at 33% (0.33)
                $mw = $entry->points / ($matchesPlayed * 3);
                $mwPercentages[$entry->id] = max($mw, 0.33); 
            }
        }

        // Map who played who
        foreach ($matches as $match) {
            if ($match->player1_entry_id && $match->player2_entry_id) {
                $opponents[$match->player1_entry_id][] = $match->player2_entry_id;
                $opponents[$match->player2_entry_id][] = $match->player1_entry_id;
            }
        }

        // 2. Calculate OMW% (Average of your opponents' MW%)
        foreach ($entries as $entry) {
            $myOpponents = $opponents[$entry->id];
            if (count($myOpponents) > 0) {
                $sum = 0;
                foreach ($myOpponents as $oppId) {
                    $sum += $mwPercentages[$oppId];
                }
                // Store as a percentage (e.g., 55.50 instead of 0.555)
                $entry->omw_percentage = round(($sum / count($myOpponents)) * 100, 2);
            } else {
                $entry->omw_percentage = 33.00; // Floor
            }
        }

        // 3. Calculate OOMW% (Average of your opponents' OMW%)
        foreach ($entries as $entry) {
            $myOpponents = $opponents[$entry->id];
            if (count($myOpponents) > 0) {
                $sum = 0;
                foreach ($myOpponents as $oppId) {
                    $oppEntry = $entries->firstWhere('id', $oppId);
                    $sum += $oppEntry ? $oppEntry->omw_percentage : 33.00;
                }
                $entry->oomw_percentage = round($sum / count($myOpponents), 2);
            } else {
                $entry->oomw_percentage = 33.00;
            }
        }

        // 4. Sort Entries to determine Rank
        // Order: Points -> OMW% -> OOMW%
        $sortedEntries = $entries->sort(function ($a, $b) {
            if ($a->points !== $b->points) {
                return $b->points <=> $a->points;
            }
            if ($a->omw_percentage !== $b->omw_percentage) {
                return $b->omw_percentage <=> $a->omw_percentage;
            }
            if ($a->oomw_percentage !== $b->oomw_percentage) {
                return $b->oomw_percentage <=> $a->oomw_percentage;
            }
            return 0;
        })->values();

        // 5. Assign Rank and Save to Database
        $rank = 1;
        foreach ($sortedEntries as $entry) {
            $entry->rank = $rank++;
            $entry->save(); // This persists rank, omw%, and oomw% all at once
        }
    }
}