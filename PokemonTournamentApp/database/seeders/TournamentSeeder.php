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
        set_time_limit(0); // <-- Fixes the 10-tournament timeout limit
        
        $this->eloCalculator = $eloCalculator;
        $this->pairingService = $pairingService;
        $this->tournamentServices = $tournamentServices;

        $players = User::whereNot('role', 2)->get();

        $this->command->info("--- Generating 50 Completed Tournaments ---");
        
        $dateTracker = now()->subDays(50);

        for ($i = 1; $i <= 50; $i++) {
            // Processing each tournament inside a transaction makes it exponentially faster
            DB::transaction(function () use ($dateTracker, $players) {
                $this->generateTournament('completed', clone $dateTracker, $players);
            });
            $dateTracker->addDay();
            if ($i % 10 == 0) $this->command->info("... $i / 50 Completed");
        }

        $this->command->info("--- Generating 3 Active Tournaments ---");
        for ($i = 1; $i <= 3; $i++) {
            DB::transaction(function () use ($players) {
                $this->generateTournament('active', now()->subHours(rand(1, 5)), $players);
            });
        }

        $this->command->info("--- Generating 2 Registration Tournaments ---");
        for ($i = 1; $i <= 2; $i++) {
            $this->generateTournament('registration', now()->addDays(rand(1, 5)), $players);
        }

        $this->command->info("Tournament Seeder finished successfully!");
    }

    private function generateTournament(string $status, Carbon $date, $allPlayers)
    {
        $playerCount = rand(4, 16);
        $totalRounds = max(3, ceil(log($playerCount, 2))); 

        $tournament = Tournament::create([
            'name'              => 'Gym Battle - ' . $date->format('Y-m-d'),
            'start_date'        => $date,
            'total_rounds'      => $totalRounds,
            'capacity'          => 16,
            'registered_player' => $playerCount,
            'status'            => $status === 'registration' ? 'registration' : 'active' 
        ]);

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
            if ($rand <= 35) $resultCode = 1;
            elseif ($rand <= 70) $resultCode = 2;
            else $resultCode = 3;

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