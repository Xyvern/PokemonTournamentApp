<?php

namespace Database\Seeders;

use App\Models\Archetype;
use App\Models\Deck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\EloCalculator; 
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentSeeder extends Seeder
{
    public function run(EloCalculator $eloCalculator): void
    {
        // 0. Prerequisites: Ensure Deck ID 1 exists
        if (!Deck::find(1)) {
            $dummyUser = User::first() ?? User::factory()->create();
            
            DB::table('decks')->insert([
                'id' => 1,
                'user_id' => $dummyUser->id,
                'global_deck_id' => 1, 
                'name' => 'Standard Deck',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 1. Create the Tournament
        $tournament = Tournament::updateOrCreate(
            ['id' => 1],
            [
                'name' => '32-Player Swiss Cup',
                'start_date' => now()->subHours(5),
                'total_rounds' => 5,
                'capacity' => 32,
                'registered_player' => 32,
                'status' => 'completed'
            ]
        );

        $this->command->info("Tournament '{$tournament->name}' ready.");

        // 2. Register 32 Players
        $entries = collect();

        for ($i = 1; $i <= 32; $i++) {
            $user = User::find($i);
            if (!$user) {
                $user = User::factory()->create([
                    'id' => $i, 
                    'name' => "Player {$i}",
                    'elo' => 1000, 
                    'matches_played' => 0,
                    'matches_won' => 0,
                    'matches_lost' => 0
                ]);
            } else {
                // Reset stats for clean seed
                $user->update([
                    'elo' => 1000,
                    'matches_played' => 0,
                    'matches_won' => 0,
                    'matches_lost' => 0
                ]);
            }

            $entry = TournamentEntry::updateOrCreate(
                ['tournament_id' => $tournament->id, 'user_id' => $user->id],
                [
                    'deck_id' => 1,
                    'points' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'ties' => 0,
                    'omw_percentage' => 0.00,
                    'oomw_percentage' => 0.00,
                    'total_elo_gain' => 0,
                ]
            );
            
            // Reload entry to get relationships
            $entries->push($entry->fresh(['user', 'deck.archetype']));
        }

        $this->command->info("32 Players registered and reset.");

        // 3. Simulate 5 Rounds
        for ($round = 1; $round <= 5; $round++) {
            $this->command->info("Simulating Round {$round}...");

            // Swiss Pairing: Shuffle then sort by points
            $sortedEntries = $entries->sortByDesc('points')->values();

            // Simple pairing: 0vs1, 2vs3, etc.
            // (Note: This is simplified. Real Swiss prevents repeat matchups)
            for ($i = 0; $i < 32; $i += 2) {
                $p1Entry = $sortedEntries[$i];
                $p2Entry = $sortedEntries[$i + 1];

                $p1User = $p1Entry->user;
                $p2User = $p2Entry->user;

                // Determine Winner
                // Force User 1 to always win so we can test the leaderboard
                $resultCode = 0;
                $winnerString = 'draw';

                if ($p1User->id === 1) {
                    $resultCode = 1;
                    $winnerString = 'player1';
                } elseif ($p2User->id === 1) {
                    $resultCode = 2;
                    $winnerString = 'player2';
                } else {
                    $resultCode = rand(1, 2);
                    $winnerString = ($resultCode === 1) ? 'player1' : 'player2';
                }

                // --- A. CALCULATE ELO ---
                // Now returns ABSOLUTE POSITIVE INTEGER
                $eloChange = $eloCalculator->eloChange(43, $p1User->elo, $p2User->elo, $winnerString);

                // --- B. UPDATE STATS & ELO ---
                if ($resultCode === 1) {
                    // P1 Wins
                    $p1Entry->wins++;
                    $p1Entry->points += 3;
                    $p1Entry->total_elo_gain += $eloChange; // Add positive
                    
                    $p1User->matches_won++;
                    $p1User->elo += $eloChange; // Add positive
                    
                    $p2Entry->losses++;
                    $p2Entry->total_elo_gain -= $eloChange; // Subtract positive
                    
                    $p2User->matches_lost++;
                    $p2User->elo -= $eloChange; // Subtract positive

                    // Archetype
                    if ($p1Entry->deck && $p1Entry->deck->archetype) {
                        $p1Entry->deck->archetype->increment('wins');
                    }
                    
                } elseif ($resultCode === 2) {
                    // P2 Wins
                    $p2Entry->wins++;
                    $p2Entry->points += 3;
                    $p2Entry->total_elo_gain += $eloChange; // Add positive
                    
                    $p2User->matches_won++;
                    $p2User->elo += $eloChange; // Add positive
                    
                    $p1Entry->losses++;
                    $p1Entry->total_elo_gain -= $eloChange; // Subtract positive
                    
                    $p1User->matches_lost++;
                    $p1User->elo -= $eloChange; // Subtract positive
                    
                    // Archetype
                    if ($p2Entry->deck && $p2Entry->deck->archetype) {
                        $p2Entry->deck->archetype->increment('wins');
                    }
                }
                
                // Global User Stats
                $p1User->matches_played++;
                $p2User->matches_played++;
                $p1Entry->deck->archetype->increment('times_played');
                $p2Entry->deck->archetype->increment('times_played');
                
                // Save Everything
                $p1User->save();
                $p2User->save();
                $p1Entry->save();
                $p2Entry->save();

                // --- C. CREATE MATCH RECORD ---
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $round,
                    'player1_entry_id' => $p1Entry->id,
                    'player2_entry_id' => $p2Entry->id,
                    'result_code' => $resultCode,
                    'elo_gain' => $eloChange // Store magnitude
                ]);
            }
        }

        // 4. Calculate OMW% and OOMW%
        $this->command->info("Calculating Tiebreakers...");
        
        // Refresh entries from DB to get latest points
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        
        // --- MW% Calculation ---
        $mwPercentages = [];
        foreach ($entries as $entry) {
            $totalMatches = $entry->matches()->count(); // Helper method on model assumed
            // Avoid division by zero
            $mwPercentages[$entry->id] = $totalMatches > 0 ? $entry->points / ($totalMatches * 3) : 0;
            
            // Standard Magic Rule: MW% cannot be lower than 0.33
            if ($mwPercentages[$entry->id] < 0.33) $mwPercentages[$entry->id] = 0.33;
        }

        // --- OMW% Calculation ---
        foreach ($entries as $entry) {
            $opponents = collect();
            
            // Assuming relationship: matches() returns HasMany or similar
            // If relationship doesn't exist, use manual query:
            $matches = TournamentMatch::where('player1_entry_id', $entry->id)
                        ->orWhere('player2_entry_id', $entry->id)
                        ->get();

            foreach ($matches as $match) {
                $oppId = ($match->player1_entry_id == $entry->id) 
                    ? $match->player2_entry_id 
                    : $match->player1_entry_id;
                if ($oppId) $opponents->push($oppId);
            }

            $omwSum = 0;
            if ($opponents->count() > 0) {
                foreach ($opponents as $oppId) {
                    $omwSum += $mwPercentages[$oppId] ?? 0.33;
                }
                $entry->omw_percentage = $omwSum / $opponents->count();
            } else {
                $entry->omw_percentage = 0.33;
            }
            $entry->save();
        }

        // --- OOMW% Calculation ---
        // Refresh to get updated OMW%
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        $omwMap = $entries->pluck('omw_percentage', 'id');

        foreach ($entries as $entry) {
            $opponents = collect();
            $matches = TournamentMatch::where('player1_entry_id', $entry->id)
                        ->orWhere('player2_entry_id', $entry->id)
                        ->get();

            foreach ($matches as $match) {
                $oppId = ($match->player1_entry_id == $entry->id) 
                    ? $match->player2_entry_id 
                    : $match->player1_entry_id;
                if ($oppId) $opponents->push($oppId);
            }

            $oomwSum = 0;
            if ($opponents->count() > 0) {
                foreach ($opponents as $oppId) {
                    $oomwSum += $omwMap[$oppId] ?? 0.33;
                }
                $entry->oomw_percentage = $oomwSum / $opponents->count();
            } else {
                $entry->oomw_percentage = 0.33;
            }
            $entry->save();
        }

        // 5. Final Ranking
        $finalStandings = TournamentEntry::where('tournament_id', $tournament->id)
            ->orderByDesc('points')
            ->orderByDesc('omw_percentage')
            ->orderByDesc('oomw_percentage')
            ->get();

        $rank = 1;
        foreach ($finalStandings as $standing) {
            $standing->rank = $rank;
            $standing->save();
            $rank++;
        }

        $this->command->info("Tournament Simulation Complete!");
    }
}