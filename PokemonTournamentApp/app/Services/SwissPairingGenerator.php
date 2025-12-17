<?php

namespace App\Services;

use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SwissPairingGenerator
{
    /**
     * Generate and save Swiss pairings for a specific round.
     *
     * @param Collection<int, TournamentEntry> $entries
     * @param int $roundNumber
     * @return void
     */
    public function generatePairings(Collection $entries, int $roundNumber): void
    {
        if ($entries->isEmpty()) {
            return;
        }

        // 1. Context Setup
        $tournamentId = $entries->first()->tournament_id;
        
        // 2. Build History (Who played whom? Who had a bye?)
        // We fetch all previous matches for this tournament to strictly avoid duplicate matchups.
        $previousMatches = TournamentMatch::where('tournament_id', $tournamentId)
            ->whereNotNull('player2_entry_id') // Ignore Byes for pairing history
            ->get();

        $playedAgainst = [];
        $receivedBye = [];

        // Load Bye History
        $byeMatches = TournamentMatch::where('tournament_id', $tournamentId)
            ->whereNull('player2_entry_id')
            ->get();
            
        foreach ($byeMatches as $match) {
            $receivedBye[$match->player1_entry_id] = true;
        }

        // Load Match History
        foreach ($previousMatches as $match) {
            $p1 = $match->player1_entry_id;
            $p2 = $match->player2_entry_id;
            $playedAgainst[$p1][] = $p2;
            $playedAgainst[$p2][] = $p1;
        }

        // 3. Sort Entries (Swiss Order)
        // Order: Points > OMW% > OOMW% > Random (to break pure ties)
        $sortedEntries = $entries->sort(function ($a, $b) {
            // Points (Higher is better)
            if ($a->points !== $b->points) {
                return $b->points <=> $a->points;
            }
            // OMW Percentage (Higher is better)
            if ($a->omw_percentage !== $b->omw_percentage) {
                return $b->omw_percentage <=> $a->omw_percentage;
            }
            // OOMW Percentage (Higher is better)
            if ($a->oomw_percentage !== $b->oomw_percentage) {
                return $b->oomw_percentage <=> $a->oomw_percentage;
            }
            // Random tiebreaker (optional, keeps it fair)
            return rand(-1, 1);
        })->values(); // Reset keys for easy iteration

        $matchesToInsert = [];
        $pairedIds = []; // Track IDs of players already paired in this loop

        // 4. Handle Odd Number of Players (The Bye)
        // The Bye goes to the lowest ranked player who hasn't had a Bye yet.
        if ($sortedEntries->count() % 2 !== 0) {
            $byeCandidateIndex = null;

            // Iterate backwards (from lowest rank to highest)
            for ($i = $sortedEntries->count() - 1; $i >= 0; $i--) {
                $entry = $sortedEntries[$i];
                if (!isset($receivedBye[$entry->id])) {
                    $byeCandidateIndex = $i;
                    break;
                }
            }

            // If everyone had a bye (rare), just give it to the absolute last place
            if ($byeCandidateIndex === null) {
                $byeCandidateIndex = $sortedEntries->count() - 1;
            }

            $byeEntry = $sortedEntries[$byeCandidateIndex];
            
            // Add Bye Match
            $matchesToInsert[] = [
                'tournament_id' => $tournamentId,
                'round_number' => $roundNumber,
                'player1_entry_id' => $byeEntry->id,
                'player2_entry_id' => null, // This signifies a Bye
                'result_code' => 1, // Automatically give the win (1 = P1 Win)
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Remove from pool so they don't get paired again
            $pairedIds[$byeEntry->id] = true;
            $sortedEntries->forget($byeCandidateIndex);
        }

        // Re-index after bye removal
        $sortedEntries = $sortedEntries->values();

        // 5. Generate Pairings (Greedy Algorithm)
        $count = $sortedEntries->count();
        
        for ($i = 0; $i < $count; $i++) {
            $player1 = $sortedEntries[$i];

            // If already paired, skip
            if (isset($pairedIds[$player1->id])) {
                continue;
            }

            $opponentFound = false;

            // Look ahead for the best valid opponent
            for ($j = $i + 1; $j < $count; $j++) {
                $potentialOpponent = $sortedEntries[$j];

                // Skip if opponent already paired
                if (isset($pairedIds[$potentialOpponent->id])) {
                    continue;
                }

                // Check History: Have they played before?
                $p1PlayedHistory = $playedAgainst[$player1->id] ?? [];
                
                if (!in_array($potentialOpponent->id, $p1PlayedHistory)) {
                    // Valid Match Found!
                    $matchesToInsert[] = [
                        'tournament_id' => $tournamentId,
                        'round_number' => $roundNumber,
                        'player1_entry_id' => $player1->id,
                        'player2_entry_id' => $potentialOpponent->id,
                        'result_code' => null, // Pending result
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $pairedIds[$player1->id] = true;
                    $pairedIds[$potentialOpponent->id] = true;
                    $opponentFound = true;
                    break;
                }
            }

            // Fallback: If no unique opponent exists (e.g., late rounds in small tourneys),
            // pair with the next available player regardless of history.
            if (!$opponentFound) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $potentialOpponent = $sortedEntries[$j];
                    if (!isset($pairedIds[$potentialOpponent->id])) {
                         $matchesToInsert[] = [
                            'tournament_id' => $tournamentId,
                            'round_number' => $roundNumber,
                            'player1_entry_id' => $player1->id,
                            'player2_entry_id' => $potentialOpponent->id,
                            'result_code' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $pairedIds[$player1->id] = true;
                        $pairedIds[$potentialOpponent->id] = true;
                        break;
                    }
                }
            }
        }

        // 6. Bulk Insert
        if (!empty($matchesToInsert)) {
            TournamentMatch::insert($matchesToInsert);
        }
    }
}