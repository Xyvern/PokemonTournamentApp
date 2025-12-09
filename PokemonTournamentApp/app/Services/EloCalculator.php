<?php

namespace App\Services;

use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class EloCalculator
{
    function expectedScore($playerElo, $opponentElo)
    {
        $exponent = ($opponentElo - $playerElo) / 400;
        return 1 / (1 + pow(10, $exponent));
    }
    
    function eloChange($k=43, $ratingA, $ratingB, $winner) 
    {
        $expectedScoreA = $this->expectedScore($ratingA, $ratingB);

        if ($winner === 'player1') {
            $actualScoreA = 1;
        } elseif ($winner === 'player2') {
            $actualScoreA = 0;
        } else {
            $actualScoreA = 0.5;
        }

        $change = $k * ($actualScoreA - $expectedScoreA);

        return round($change);
    }
    function updateEloRatings($player1Id, $player2Id, $winner, $matchId)
    {
        $player1 = DB::table('users')->where('id', $player1Id)->first();
        $player2 = DB::table('users')->where('id', $player2Id)->first();

        $ratingA = $player1->elo;
        $ratingB = $player2->elo;

        $eloChange = $this->eloChange(43, $ratingA, $ratingB, $winner);
        TournamentMatch::where('id', $matchId)->update(['elo_gain' => $eloChange]);
        if ($winner === 'player1') {
            $newRatingA = $ratingA + $eloChange;
            $newRatingB = $ratingB - $eloChange;
            TournamentEntry::where('user_id', $player1Id)->update(['total_elo_gain' => $eloChange]);
            TournamentEntry::where('user_id', $player2Id)->update(['total_elo_gain' => $eloChange*-1]);
        } elseif ($winner === 'player2') {
            $newRatingA = $ratingA - $eloChange;
            $newRatingB = $ratingB + $eloChange;
            TournamentEntry::where('user_id', $player1Id)->update(['total_elo_gain' => $eloChange*-1]);
            TournamentEntry::where('user_id', $player2Id)->update(['total_elo_gain' => $eloChange]);
        } else {
            $newRatingA = $ratingA + round($eloChange / 2);
            $newRatingB = $ratingB - round($eloChange / 2);
            TournamentEntry::where('user_id', $player1Id)->update(['total_elo_gain' => round($eloChange / 2)]);
            TournamentEntry::where('user_id', $player2Id)->update(['total_elo_gain' => round($eloChange / 2)]);
        }

        DB::table('users')->where('id', $player1Id)->update(['elo' => $newRatingA]);
        DB::table('users')->where('id', $player2Id)->update(['elo' => $newRatingB]);
    }
}