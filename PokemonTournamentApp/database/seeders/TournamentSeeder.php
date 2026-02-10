<?php

namespace Database\Seeders;

use App\Models\Deck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\EloCalculator;
use App\Services\SwissPairingGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentSeeder extends Seeder
{
    protected $eloCalculator;
    protected $pairingService;

    public function run(EloCalculator $eloCalculator, SwissPairingGenerator $pairingService): void
    {
        $this->eloCalculator = $eloCalculator;
        $this->pairingService = $pairingService;

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

        // --- SCENARIO 1: COMPLETED TOURNAMENT (ID 1) ---
        $this->seedCompletedTournament();

        // --- SCENARIO 2: REGISTRATION TOURNAMENT (ID 2) ---
        $this->seedRegistrationTournament();

        // --- SCENARIO 3: ACTIVE TOURNAMENT (ID 3) ---
        $this->seedActiveTournament();
    }

    /**
     * Scenario 1: The original 32-player completed tournament
     */
    private function seedCompletedTournament()
    {
        $this->command->info("--- Seeding Completed Tournament (ID 1) ---");

        $tournament = Tournament::updateOrCreate(['id' => 1], [
            'name' => '32-Player Swiss Cup (Completed)',
            'start_date' => now()->subDays(1),
            'total_rounds' => 5,
            'capacity' => 32,
            'registered_player' => 32,
            'status' => 'completed'
        ]);

        // Register 32 Players
        $this->registerPlayers($tournament, 32);

        // Simulate all 5 Rounds
        for ($round = 1; $round <= 5; $round++) {
            $this->command->info("Simulating Round {$round}...");
            $currentEntries = TournamentEntry::where('tournament_id', $tournament->id)->get();
            
            // Generate Pairings
            $this->pairingService->generatePairings($currentEntries, $round);
            
            // Resolve Matches (Set winners)
            $this->simulateRoundResults($tournament, $round);
        }

        // Calculate Tiebreakers & Final Rank
        $this->calculateTiebreakers($tournament);
        $this->assignRanks($tournament);
    }

    /**
     * Scenario 2: Tournament in 'Registration' phase with users 1-10
     */
    private function seedRegistrationTournament()
    {
        $this->command->info("--- Seeding Registration Tournament (ID 2) ---");

        $tournament = Tournament::updateOrCreate(['id' => 2], [
            'name' => 'Beginner Cup (Registration)',
            'start_date' => now()->addDays(2),
            'total_rounds' => 4,
            'capacity' => 64,
            'registered_player' => 10,
            'status' => 'registration' // Status is explicitly registration
        ]);

        // Register Users 1-10 specifically
        for ($i = 1; $i <= 10; $i++) {
            $user = User::find($i) ?? User::factory()->create(['id' => $i, 'name' => "Player {$i}"]);
            
            TournamentEntry::updateOrCreate(
                ['tournament_id' => $tournament->id, 'user_id' => $user->id],
                [
                    'deck_id' => 1,
                    'points' => 0, 'wins' => 0, 'losses' => 0, 'ties' => 0,
                    'omw_percentage' => 0.00, 'oomw_percentage' => 0.00, 'total_elo_gain' => 0,
                ]
            );
        }
        $this->command->info("Registered Users 1-10 for Tournament 2.");
    }

    /**
     * Scenario 3: Active Tournament (Round 3 in progress)
     */
    private function seedActiveTournament()
    {
        $this->command->info("--- Seeding Active Tournament (ID 3) ---");

        $tournament = Tournament::updateOrCreate(['id' => 3], [
            'name' => 'Mid-Week Grinder (Round 3 Active)',
            'start_date' => now()->subHours(2),
            'total_rounds' => 5,
            'capacity' => 16,
            'registered_player' => 16,
            'status' => 'active' // Status is active
        ]);

        // Register 16 Players
        $this->registerPlayers($tournament, 16);

        // Simulate Round 1 (Completed)
        $this->command->info("Simulating Round 1 (Done)...");
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        $this->pairingService->generatePairings($entries, 1);
        $this->simulateRoundResults($tournament, 1);

        // Simulate Round 2 (Completed)
        $this->command->info("Simulating Round 2 (Done)...");
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        $this->pairingService->generatePairings($entries, 2);
        $this->simulateRoundResults($tournament, 2);

        // Simulate Round 3 (In Progress - Pairings Generated, No Results)
        $this->command->info("Generating Round 3 Pairings (Active)...");
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        $this->pairingService->generatePairings($entries, 3);
        
        // NOTE: We do NOT call simulateRoundResults for Round 3. 
        // Matches exist in DB but result_code is null/pending.

        // Calculate interim standings/tiebreakers based on R1 & R2
        $this->calculateTiebreakers($tournament);
        $this->assignRanks($tournament);
    }

    /**
     * Helper: Registers N dummy players
     */
    private function registerPlayers(Tournament $tournament, int $count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $user = User::find($i);
            if (!$user) {
                $user = User::factory()->create([
                    'id' => $i,
                    'name' => "Player {$i}",
                    'elo' => 1000
                ]);
            }

            TournamentEntry::updateOrCreate(
                ['tournament_id' => $tournament->id, 'user_id' => $user->id],
                [
                    'deck_id' => 1,
                    'points' => 0, 'wins' => 0, 'losses' => 0, 'ties' => 0,
                    'omw_percentage' => 0.00, 'oomw_percentage' => 0.00, 'total_elo_gain' => 0,
                ]
            );
        }
    }

    /**
     * Helper: Sets random winners for a specific round
     */
    private function simulateRoundResults(Tournament $tournament, int $round)
    {
        $matches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', $round)
            ->with(['player1.user', 'player2.user', 'player1.deck.archetype', 'player2.deck.archetype'])
            ->get();

        foreach ($matches as $match) {
            // Handle Bye
            if (!$match->player2_entry_id) {
                $p1Entry = $match->player1;
                $p1Entry->wins++;
                $p1Entry->points += 3;
                $p1Entry->save();
                $match->update(['result_code' => 1]); // 1 = P1 Win (Bye)
                continue;
            }

            $p1Entry = $match->player1;
            $p2Entry = $match->player2;
            $p1User = $p1Entry->user;
            $p2User = $p2Entry->user;

            // Pick Winner (Bias towards ID 1 for testing consistency, otherwise random)
            if ($p1User->id === 1) $resultCode = 1;
            elseif ($p2User->id === 1) $resultCode = 2;
            else $resultCode = rand(1, 2);

            $winnerString = ($resultCode === 1) ? 'player1' : 'player2';

            // Calculate ELO
            $eloChange = $this->eloCalculator->eloChange(43, $p1User->elo, $p2User->elo, $winnerString);

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

                // Update Archetypes (if relationship exists)
                if ($p1Entry->deck && $p1Entry->deck->archetype) $p1Entry->deck->archetype->increment('wins');

            } else {
                // P2 Wins
                $p2Entry->wins++;
                $p2Entry->points += 3;
                $p2Entry->total_elo_gain += $eloChange;
                $p2User->matches_won++;
                $p2User->elo += $eloChange;

                $p1Entry->losses++;
                $p1Entry->total_elo_gain -= $eloChange;
                $p1User->matches_lost++;
                $p1User->elo -= $eloChange;

                if ($p2Entry->deck && $p2Entry->deck->archetype) $p2Entry->deck->archetype->increment('wins');
            }

            // Global User Stats
            $p1User->matches_played++;
            $p2User->matches_played++;
            
            // Global Archetype Stats
            if($p1Entry->deck && $p1Entry->deck->archetype) $p1Entry->deck->archetype->increment('times_played');
            if($p2Entry->deck && $p2Entry->deck->archetype) $p2Entry->deck->archetype->increment('times_played');

            $p1User->save();
            $p2User->save();
            $p1Entry->save();
            $p2Entry->save();

            $match->update([
                'result_code' => $resultCode,
                'elo_gain' => $eloChange
            ]);
        }
    }

    /**
     * Helper: Calculates OMW and OOMW percentages
     */
    private function calculateTiebreakers(Tournament $tournament)
    {
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        
        // 1. Calculate Match Win % (MW%)
        $mwPercentages = [];
        foreach ($entries as $entry) {
            $totalMatches = $entry->wins + $entry->losses + $entry->ties;
            // Avoid division by zero
            $mw = $totalMatches > 0 ? $entry->points / ($totalMatches * 3) : 0;
            // Rule: Minimum 33%
            if ($mw < 0.33) $mw = 0.33;
            $mwPercentages[$entry->id] = $mw;
        }

        // 2. Calculate OMW%
        foreach ($entries as $entry) {
            $opponents = $this->getOpponentIds($entry);
            
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

        // 3. Calculate OOMW%
        // Refresh entries to get updated OMW%
        $entries = TournamentEntry::where('tournament_id', $tournament->id)->get();
        $omwMap = $entries->pluck('omw_percentage', 'id');

        foreach ($entries as $entry) {
            $opponents = $this->getOpponentIds($entry);

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
    }

    private function getOpponentIds($entry)
    {
        $opponents = collect();
        
        // Find all matches for this entry that have a result (or just exist, depending on rules)
        // Usually OMW includes current opponents even if match isn't finished, 
        // but here we only query matches that exist in DB.
        $matches = TournamentMatch::where(function($q) use ($entry) {
                $q->where('player1_entry_id', $entry->id)
                  ->orWhere('player2_entry_id', $entry->id);
            })
            ->whereNotNull('player2_entry_id') // Exclude byes
            ->get();

        foreach ($matches as $match) {
            $oppId = ($match->player1_entry_id == $entry->id) 
                ? $match->player2_entry_id 
                : $match->player1_entry_id;
            $opponents->push($oppId);
        }
        
        return $opponents;
    }

    private function assignRanks(Tournament $tournament)
    {
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
    }
}