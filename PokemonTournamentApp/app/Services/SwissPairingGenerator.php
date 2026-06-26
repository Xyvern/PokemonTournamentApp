<?php

namespace App\Services;

use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $previousMatches = TournamentMatch::where('tournament_id', $tournamentId)
            ->whereNotNull('player2_entry_id') 
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
            return rand(-1, 1);
        })->values(); 

        $matchesToInsert = [];
        $pairedIds = []; 

        // 4. Handle Odd Number of Players (The Bye)
        if ($sortedEntries->count() % 2 !== 0) {
            $byeCandidateIndex = null;

            for ($i = $sortedEntries->count() - 1; $i >= 0; $i--) {
                $entry = $sortedEntries[$i];
                if (!isset($receivedBye[$entry->id])) {
                    $byeCandidateIndex = $i;
                    break;
                }
            }

            if ($byeCandidateIndex === null) {
                $byeCandidateIndex = $sortedEntries->count() - 1;
            }

            $byeEntry = $sortedEntries[$byeCandidateIndex];
            
            // Add Bye Match
            $matchesToInsert[] = [
                'tournament_id' => $tournamentId,
                'round_number' => $roundNumber,
                'player1_entry_id' => $byeEntry->id,
                'player2_entry_id' => null, 
                'result_code' => 1, 
                'room_code' => Str::uuid()->toString(), // ADDED
                'starting_player' => $byeEntry->user_id, // ADDED: Solo player is starting player
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $pairedIds[$byeEntry->id] = true;
            $sortedEntries->forget($byeCandidateIndex);
        }

        $sortedEntries = $sortedEntries->values();

        // 5. Generate Pairings (Greedy Algorithm)
        $count = $sortedEntries->count();
        
        for ($i = 0; $i < $count; $i++) {
            $player1 = $sortedEntries[$i];

            if (isset($pairedIds[$player1->id])) {
                continue;
            }

            $opponentFound = false;

            for ($j = $i + 1; $j < $count; $j++) {
                $potentialOpponent = $sortedEntries[$j];

                if (isset($pairedIds[$potentialOpponent->id])) {
                    continue;
                }

                $p1PlayedHistory = $playedAgainst[$player1->id] ?? [];
                
                if (!in_array($potentialOpponent->id, $p1PlayedHistory)) {
                    // Valid Match Found!
                    $matchesToInsert[] = [
                        'tournament_id' => $tournamentId,
                        'round_number' => $roundNumber,
                        'player1_entry_id' => $player1->id,
                        'player2_entry_id' => $potentialOpponent->id,
                        'result_code' => null, 
                        'room_code' => Str::uuid()->toString(), // ADDED
                        'starting_player' => rand(0, 1) ? $player1->user_id : $potentialOpponent->user_id, // ADDED
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $pairedIds[$player1->id] = true;
                    $pairedIds[$potentialOpponent->id] = true;
                    $opponentFound = true;
                    break;
                }
            }

            // Fallback: If no unique opponent exists
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
                            'room_code' => Str::uuid()->toString(), // ADDED
                            'starting_player' => rand(0, 1) ? $player1->user_id : $potentialOpponent->user_id, // ADDED
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