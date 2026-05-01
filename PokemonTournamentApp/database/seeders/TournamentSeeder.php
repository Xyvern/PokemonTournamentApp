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
            $dateTracker->addWeek(); // Move to the next Friday
            $completedCount++;
            
            if ($completedCount % 10 == 0) {
                $this->command->info("... {$completedCount} Tournaments Completed");
            }
        }

        $this->command->info("--- Generating 1 Active (Live) Tournament ---");
        DB::transaction(function () use ($players) {
            // A live tournament happening today
            $this->generateTournament('active', now(), $players);
        });

        $this->command->info("--- Generating 5 Upcoming (Registration) Tournaments ---");
        // Start scheduling for the next upcoming Friday
        $upcomingTracker = now()->next(Carbon::FRIDAY);
        
        for ($i = 1; $i <= 5; $i++) {
            $this->generateTournament('registration', clone $upcomingTracker, $players);
            $upcomingTracker->addWeek(); // Schedule one for each future Friday
        }

        $this->command->info("Tournament Seeder finished successfully!");
    }

    private function generateTournament(string $status, Carbon $date, $allPlayers)
    {
        // Hardcode the start time to exactly 19:00:00
        $date->setTime(19, 0, 0);

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
        
        // Explicitly set timestamps (Created 1 week before, Updated on the day of)
        $tournament->timestamps = false; 
        $tournament->created_at = $date->copy()->subWeek();
        $tournament->updated_at = $date->copy();
        $tournament->save();

        $selectedPlayers = $allPlayers->random($playerCount);
        
        foreach ($selectedPlayers as $player) {
            $deck = Deck::where('user_id', $player->id)->inRandomOrder()->first();
            TournamentEntry::create([
                'tournament_id'   => $tournament->id,
                'user_id'         => $player->id,
                'deck_id'         => $deck ? $deck->id : 1,
            ]);
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

        if ($status === 'completed') {
            $tournament->status = 'completed';
            
            // Disable timestamps again so the save() doesn't overwrite our historical updated_at
            $tournament->timestamps = false;
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

            // 3. New Win/Loss/Tie Logic (10% Tie Chance)
            $rand = rand(1, 100);
            if ($rand <= 45) {
                $resultCode = 1; // 45% chance Player 1 wins
            } elseif ($rand <= 90) {
                $resultCode = 2; // 45% chance Player 2 wins
            } else {
                $resultCode = 3; // 10% chance Tie
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