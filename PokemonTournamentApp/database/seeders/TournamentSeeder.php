<?php

namespace Database\Seeders;

use App\Models\Deck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\EloCalculator;
use App\Services\SwissPairingGenerator;
use App\Services\TournamentServices;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TournamentSeeder extends Seeder
{
    protected $eloCalculator;
    protected $pairingService;
    protected $tournamentServices;

    public function run(
        EloCalculator $eloCalculator, 
        SwissPairingGenerator $pairingService,
        TournamentServices $tournamentServices
    ): void {
        set_time_limit(0); 
        
        $this->eloCalculator = $eloCalculator;
        $this->pairingService = $pairingService;
        $this->tournamentServices = $tournamentServices;

        $players = User::whereNot('role', 2)->get();

        $this->command->info("--- Generating Completed Tournaments (Fridays: June 2025 - Apr 2026) ---");
        
        // Start: First Friday of June 2025
        $dateTracker = Carbon::parse('2025-06-06');
        // End: Last Friday before today (April 20, 2026)
        $endDate = Carbon::parse('2026-04-17'); 
        $completedCount = 0;

        while ($dateTracker->lte($endDate)) {
            DB::transaction(function () use ($dateTracker, $players) {
                $this->generateTournament('completed', clone $dateTracker, $players);
            });
            $dateTracker->addWeek(); 
            $completedCount++;
            
            if ($completedCount % 10 == 0) {
                $this->command->info("... {$completedCount} Tournaments Completed");
            }
        }

        $this->command->info("--- Generating 1 Active (Live) Tournament ---");
        DB::transaction(function () use ($players) {
            $this->generateTournament('active', now(), $players);
        });

        $this->command->info("--- Generating 5 Upcoming (Registration) Tournaments ---");
        $upcomingTracker = now()->next(Carbon::FRIDAY);
        
        for ($i = 1; $i <= 5; $i++) {
            $this->generateTournament('registration', clone $upcomingTracker, $players);
            $upcomingTracker->addWeek(); 
        }

        $this->command->info("Tournament Seeder finished successfully!");
    }

    private function generateTournament(string $status, Carbon $date, $allPlayers)
    {
        // Hardcode the start time to exactly 19:00:00
        $date->setTime(19, 0, 0);

        // Tournament was created 1 week before the start date
        $tournamentCreatedAt = $date->copy()->subWeek();

        // 1. Capacity Rules: 10 to 16 people
        $capacity = rand(10, 16);
        $playerCount = rand(10, $capacity);
        
        // Swiss rules: Usually 3 rounds for 8+, 4 rounds for 16+
        $totalRounds = max(3, ceil(log($playerCount, 2))); 

        // 2. Safely create the tournament bypassing default Laravel timestamps
        $tournament = new Tournament([
            'name'              => 'Gym Battle - ' . $date->format('Y-m-d'),
            'start_date'        => $date,
            'total_rounds'      => $totalRounds,
            'capacity'          => $capacity,
            'registered_player' => $playerCount,
            'status'            => $status === 'registration' ? 'registration' : 'active' 
        ]);
        
        $tournament->timestamps = false; 
        $tournament->created_at = $tournamentCreatedAt;
        $tournament->updated_at = $tournamentCreatedAt;
        $tournament->save();

        // 3. Realistic User Dates Logic
        $eligiblePlayers = $allPlayers->filter(function($player) use ($tournamentCreatedAt) {
            return $player->created_at < $tournamentCreatedAt;
        });

        // If we don't have enough eligible players (e.g., June 2025 tournament), we pick 
        // random players and literally "time travel" their created_at date in the database!
        if ($eligiblePlayers->count() < $playerCount) {
            $selectedPlayers = $allPlayers->random($playerCount);
            foreach ($selectedPlayers as $player) {
                if ($player->created_at >= $tournamentCreatedAt) {
                    $newDate = $tournamentCreatedAt->copy()->subDays(rand(1, 30));
                    $player->timestamps = false; // Prevent Laravel from making it "now"
                    $player->created_at = $newDate;
                    $player->updated_at = $newDate;
                    $player->save();
                }
            }
        } else {
            $selectedPlayers = $eligiblePlayers->random($playerCount);
        }
        
        // 4. Realistic Entry Dates Logic
        foreach ($selectedPlayers as $player) {
            $deck = Deck::where('user_id', $player->id)->inRandomOrder()->first();
            
            // Randomly pick a time exactly 1 to 7 days before the tournament starts
            // 1440 minutes = 1 day, 10080 minutes = 7 days
            $entryDate = $date->copy()->subMinutes(rand(1440, 10080));

            $entry = new TournamentEntry([
                'tournament_id'   => $tournament->id,
                'user_id'         => $player->id,
                'deck_id'         => $deck ? $deck->id : 1,
            ]);
            $entry->timestamps = false;
            $entry->created_at = $entryDate;
            $entry->updated_at = $entryDate;
            $entry->save();
        }

        if ($status === 'registration') return;

        $targetRound = ($status === 'completed') ? $totalRounds : rand(1, $totalRounds - 1);
        $targetRound = max(1, $targetRound);

        for ($currentRound = 1; $currentRound <= $targetRound; $currentRound++) {
            $activeEntries = TournamentEntry::where('tournament_id', $tournament->id)->where('is_dropped', false)->get();
            $this->pairingService->generatePairings($activeEntries, $currentRound);

            if ($status === 'active' && $currentRound === $targetRound) {
                $this->setupPendingMatches($tournament->id, $currentRound);
                break; 
            }

            $this->simulateRoundResults($tournament->id, $currentRound);
            $this->tournamentServices->processRoundStats($tournament->id, $currentRound, $this->eloCalculator);
            $this->tournamentServices->updateStandingsAndTiebreakers($tournament->id);
        }

        // 5. Realistic Match Dates Logic
        // Mass update all matches generated in the loop above to match the tournament's timestamps
        TournamentMatch::where('tournament_id', $tournament->id)->update([
            'created_at' => $tournament->created_at,
            'updated_at' => $tournament->updated_at
        ]);

        if ($status === 'completed') {
            $tournament->status = 'completed';
            
            // Disable timestamps so the completion save doesn't overwrite our fake historical dates
            $tournament->timestamps = false;
            $tournament->updated_at = $date->copy(); // Tournament ends on the start date
            $tournament->save();
            
            $this->tournamentServices->processArchetypeStats($tournament->id);
        }
    }

    private function simulateRoundResults(int $tournamentId, int $roundNumber)
    {
        $matches = TournamentMatch::where('tournament_id', $tournamentId)
            ->where('round_number', $roundNumber)
            ->with(['player1', 'player2'])
            ->get();

        foreach ($matches as $match) {
            $roomCode = Str::uuid()->toString();

            if (!$match->player2_entry_id) {
                $match->update([
                    'result_code' => 1,
                    'room_code' => $roomCode,
                    'starting_player' => $match->player1->user_id
                ]);
                continue;
            }

            $rand = rand(1, 100);
            if ($rand <= 48) {
                $resultCode = 1; 
            } elseif ($rand <= 96) {
                $resultCode = 2; 
            } else {
                $resultCode = 3; 
            }

            $startingPlayerId = rand(0, 1) ? $match->player1->user_id : $match->player2->user_id;

            $match->update([
                'result_code' => $resultCode,
                'room_code' => $roomCode,
                'starting_player' => $startingPlayerId
            ]);
        }
    }

    private function setupPendingMatches(int $tournamentId, int $roundNumber)
    {
        $pendingMatches = TournamentMatch::where('tournament_id', $tournamentId)
            ->where('round_number', $roundNumber)
            ->with(['player1', 'player2'])
            ->get();

        foreach ($pendingMatches as $match) {
            $startingPlayer = $match->player2_entry_id 
                ? (rand(0, 1) ? $match->player1->user_id : $match->player2->user_id) 
                : $match->player1->user_id;

            $match->update([
                'room_code' => Str::uuid()->toString(),
                'starting_player' => $startingPlayer
            ]);
        }
    }
}