<?php

namespace App\Services;

use App\Models\TournamentMatch;

class TournamentServices
{
    public function defineWinner($resultCode, $matchId)
    {
        $match = TournamentMatch::find($matchId);
        if (!$match) {
            return null;
        }else{
            if ($resultCode == 1) {
                TournamentMatch::where('id', $matchId)->update(['result_code' => TournamentMatch::RESULT_P1_WIN]);
            }elseif ($resultCode == 2) {
                TournamentMatch::where('id', $matchId)->update(['result_code' => TournamentMatch::RESULT_P2_WIN]);
            }elseif ($resultCode == 3) {
                TournamentMatch::where('id', $matchId)->update(['result_code' => TournamentMatch::RESULT_TIE]);
            }
        }
    }

    public function assignWinByes($tournamentId, $roundNumber)
    {
        $matches = TournamentMatch::where('tournament_id', $tournamentId)
            ->where('round_number', $roundNumber)
            ->with(['player1.user', 'player2.user']) 
            ->get();
        foreach ($matches as $match) {
            if (!$match->player2_entry_id) {
                $p1Entry = $match->player1;
                $p1Entry->wins++;
                $p1Entry->points += 3;
                $p1Entry->save();
                continue;
            }
        }
    }

    public function updateStatsAfterMatch($matchId, $resultCode, EloCalculator $eloCalculator)
    {
        $match = TournamentMatch::with(['player1', 'player2'])->find($matchId);
        if (!$match) {
            return null;
        }
        $player1Entry = $match->player1;
        $player2Entry = $match->player2;

        $winnerString = '';
        if ($resultCode == TournamentMatch::RESULT_P1_WIN) {
            $winnerString = 'player1';
            $player1Entry->wins++;
            $player1Entry->points += 3;
            $player2Entry->losses++;
            $player1Entry->deck->archetype?->increment('times_played');
            $player1Entry->deck->archetype?->increment('wins');
            $player2Entry->deck->archetype?->increment('times_played');
        } elseif ($resultCode == TournamentMatch::RESULT_P2_WIN) {
            $winnerString = 'player2';
            $player2Entry->wins++;
            $player2Entry->points += 3;
            $player1Entry->losses++;
            $player1Entry->deck->archetype?->increment('times_played');
            $player2Entry->deck->archetype?->increment('wins');
            $player2Entry->deck->archetype?->increment('times_played');
        } elseif ($resultCode == TournamentMatch::RESULT_TIE) {
            $winnerString = 'tie';
            $player1Entry->ties++;
            $player2Entry->ties++;
            $player1Entry->points += 1;
            $player2Entry->points += 1;
            $player1Entry->deck->archetype?->increment('times_played');
            $player2Entry->deck->archetype?->increment('times_played');
        }
        $eloCalculator->updateEloRatings($player1Entry->user->elo, $player2Entry->user->elo, $winnerString, $matchId);
        
        $player1Entry->save();
        $player2Entry->save();
    }
}