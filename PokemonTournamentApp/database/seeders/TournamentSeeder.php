<?php

namespace Database\Seeders;

use App\Models\Deck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
    {
        // 0. Prerequisites: Ensure Deck ID 1 exists
        if (!Deck::find(1)) {
            // Create a dummy user if needed to assign the deck
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

        // 1. Create the Tournament (forcing ID 1 if possible, otherwise auto-increment)
        // We use updateOrCreate to ensure we don't duplicate if seeded multiple times
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

        // 2. Register 32 Players (User IDs 1-32)
        $entries = collect();

        for ($i = 1; $i <= 32; $i++) {
            // Ensure user exists
            $user = User::find($i);
            if (!$user) {
                $user = User::factory()->create(['id' => $i, 'name' => "Player {$i}"]);
            }

            // Create Entry
            $entry = TournamentEntry::updateOrCreate(
                ['tournament_id' => $tournament->id, 'user_id' => $user->id],
                [
                    'deck_id' => 1, // Everyone uses Deck ID 1
                    'points' => 0,
                    'wins' => 0,
                    'losses' => 0,
                    'ties' => 0,
                    'omw_percentage' => 0.00,
                    'oomw_percentage' => 0.00,
                    'rank' => null
                ]
            );
            
            $entries->push($entry);
        }

        $this->command->info("32 Players registered with Deck ID 1.");

        // 3. Simulate 5 Rounds
        for ($round = 1; $round <= 5; $round++) {
            $this->command->info("Simulating Round {$round}...");

            // Swiss Logic: Sort by points desc to pair high vs high
            // Shuffle first to randomize within same-point groups
            $sortedEntries = $entries->shuffle()->sortByDesc('points')->values();

            // Since we have 32 players (even), no bye logic is needed.
            for ($i = 0; $i < 32; $i += 2) {
                $p1 = $sortedEntries[$i];
                $p2 = $sortedEntries[$i + 1];

                // Determine Winner
                // Requirement: User ID 1 must win.
                $resultCode = 0; 

                if ($p1->user_id === 1) {
                    $resultCode = 1; // P1 is User 1 -> Win
                } elseif ($p2->user_id === 1) {
                    $resultCode = 2; // P2 is User 1 -> Win
                } else {
                    // Random outcome for everyone else
                    $resultCode = rand(1, 2);
                }

                // Create Match Record
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $round,
                    'player1_entry_id' => $p1->id,
                    'player2_entry_id' => $p2->id,
                    'result_code' => $resultCode,
                    // 'table_number' => ($i / 2) + 1 // Optional if column exists
                ]);

                // Update Stats
                if ($resultCode === 1) {
                    $p1->wins++;
                    $p1->points += 3;
                    $p2->losses++;
                } else {
                    $p2->wins++;
                    $p2->points += 3;
                    $p1->losses++;
                }

                $p1->save();
                $p2->save();
            }
        }

        // 4. Final Standings Update
        $finalStandings = TournamentEntry::where('tournament_id', $tournament->id)
            ->orderByDesc('points')
            ->get();

        $rank = 1;
        foreach ($finalStandings as $standing) {
            $standing->rank = $rank;
            $standing->save();
            $rank++;
        }

        $this->command->info("Tournament Simulation Complete! User 1 (5-0) should be Rank 1.");
    }
}
