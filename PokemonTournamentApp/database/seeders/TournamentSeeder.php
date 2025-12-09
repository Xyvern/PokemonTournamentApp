<?php

namespace Database\Seeders;

use App\Models\Archetype;
use App\Models\Deck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\EloCalculator; // Import your service
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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
            // Ensure user exists with initial stats if null
            $user = User::find($i);
            if (!$user) {
                $user = User::factory()->create([
                    'id' => $i, 
                    'name' => "Player {$i}",
                    'elo' => 1200, // Default Elo
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
            
            $entries->push($entry);
        }

        $this->command->info("32 Players registered.");

        // 3. Simulate 5 Rounds
        for ($round = 1; $round <= 5; $round++) {
            $this->command->info("Simulating Round {$round}...");

            // Swiss Pairing: Shuffle then sort by points
            $sortedEntries = $entries->shuffle()->sortByDesc('points')->values();

            for ($i = 0; $i < 32; $i += 2) {
                $p1Entry = $sortedEntries[$i];
                $p2Entry = $sortedEntries[$i + 1];

                // Load User Models for Stats/Elo
                $p1User = $p1Entry->user;
                $p2User = $p2Entry->user;

                // Determine Winner (User 1 always wins)
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

                // --- A. CALCULATE ELO (Using Service) ---
                // We use your service's math, but apply updates manually to match your Model column names
                $eloChange = $eloCalculator->eloChange(43, $p1User->elo ?? 1200, $p2User->elo ?? 1200, $winnerString);

                // --- B. UPDATE STATS & ELO ---
                if ($resultCode === 1) {
                    // P1 Wins
                    $p1Entry->wins++;
                    $p1Entry->points += 3;
                    $p1Entry->total_elo_gain += $eloChange;
                    
                    $p1User->matches_won++;
                    $p1User->elo += $eloChange;
                    
                    $p2Entry->losses++;
                    $p2Entry->total_elo_gain -= $eloChange;
                    
                    $p2User->matches_lost++;
                    $p2User->elo -= $eloChange;

                    // Update Archetype Wins
                    if ($p1Entry->deck && $p1Entry->deck->archetype) {
                        $p1Entry->deck->archetype->increment('wins');
                    }
                    
                } elseif ($resultCode === 2) {
                    // P2 Wins
                    $p2Entry->wins++;
                    $p2Entry->points += 3;
                    $p2Entry->total_elo_gain += $eloChange; // Note: calc assumes winner gains
                    
                    $p2User->matches_won++;
                    $p2User->elo += $eloChange;
                    
                    $p1Entry->losses++;
                    $p1Entry->total_elo_gain -= $eloChange;
                    
                    $p1User->matches_lost++;
                    $p1User->elo -= $eloChange;
                    
                    // Update Archetype Wins
                    if ($p2Entry->deck && $p2Entry->deck->archetype) {
                        $p2Entry->deck->archetype->increment('wins');
                    }
                }
                
                // Global User Stats
                $p1User->matches_played++;
                $p2User->matches_played++;
                $p1Entry->deck->archetype->increment('times_played');
                $p2Entry->deck->archetype->increment('times_played');
                
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
                    'elo_gain' => $eloChange
                ]);
            }
        }

        // 4. Calculate OMW% and OOMW%
        $this->command->info("Calculating Tiebreakers (OMW% / OOMW%)...");
        
        // Reload all entries with their matches to ensure fresh data
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        
        // Helper to calculate Match Win %
        // Rule: Score / (Rounds * 3). Minimum of 0.33 usually applies in Magic, simplified here.
        $mwPercentages = [];
        foreach ($entries as $entry) {
            $totalPossible = $entry->matches()->count() * 3;
            $mwPercentages[$entry->id] = $totalPossible > 0 ? $entry->points / $totalPossible : 0;
        }

        foreach ($entries as $entry) {
            $opponents = collect();
            
            // Find all matches for this user
            $matches = $entry->matches();

            foreach ($matches as $match) {
                // Determine opponent ID
                $oppId = ($match->player1_entry_id == $entry->id) 
                    ? $match->player2_entry_id 
                    : $match->player1_entry_id;
                
                if ($oppId) $opponents->push($oppId);
            }

            // Calculate OMW% (Average of Opponents' MW%)
            $omwSum = 0;
            if ($opponents->count() > 0) {
                foreach ($opponents as $oppId) {
                    $omwSum += $mwPercentages[$oppId] ?? 0;
                }
                $entry->omw_percentage = $omwSum / $opponents->count();
            } else {
                $entry->omw_percentage = 0;
            }
            
            $entry->save();
        }

        // Calculate OOMW% (Average of Opponents' OMW%)
        // We need to re-fetch or use updated values.
        $omwPercentages = $entries->pluck('omw_percentage', 'id');

        foreach ($entries as $entry) {
            $opponents = collect();
            $matches = $entry->matches();
            
            foreach ($matches as $match) {
                $oppId = ($match->player1_entry_id == $entry->id) 
                    ? $match->player2_entry_id 
                    : $match->player1_entry_id;
                if ($oppId) $opponents->push($oppId);
            }

            $oomwSum = 0;
            if ($opponents->count() > 0) {
                foreach ($opponents as $oppId) {
                    $oomwSum += $omwPercentages[$oppId] ?? 0;
                }
                $entry->oomw_percentage = $oomwSum / $opponents->count();
            } else {
                $entry->oomw_percentage = 0;
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

        $this->command->info("Tournament Simulation Complete! User 1 should be Rank 1.");
    }
}