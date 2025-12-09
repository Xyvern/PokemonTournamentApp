<?php

namespace App\Services;

use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class EloCalculator
{
    /**
     * Calculate probability of Player 1 winning.
     */
    function expectedScore($playerElo, $opponentElo)
    {
        $exponent = ($opponentElo - $playerElo) / 400;
        return 1 / (1 + pow(10, $exponent));
    }
    
    /**
     * Returns the MAGNITUDE of points to exchange (Always Positive).
     */
    function eloChange($k = 43, $ratingA, $ratingB, $winner) 
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

        // FIX: Return absolute value. We handle the +/- logic in the update function.
        return round(abs($change));
    }

    /**
     * Updates the database for a real match.
     */
    function updateEloRatings($player1Id, $player2Id, $winner, $matchId)
    {
        $player1 = DB::table('users')->where('id', $player1Id)->first();
        $player2 = DB::table('users')->where('id', $player2Id)->first();

        // Default to 1000 if null
        $ratingA = $player1->elo ?? 1000;
        $ratingB = $player2->elo ?? 1000;

        // Get the magnitude of points (Always Positive integer)
        // Note: For a draw, this calculates the small difference based on who was favored.
        $eloChange = $this->eloChange(43, $ratingA, $ratingB, $winner);

        // Update Match Record
        TournamentMatch::where('id', $matchId)->update(['elo_gain' => $eloChange]);

        if ($winner === 'player1') {
            // --- PLAYER 1 WINS ---
            // P1 Gains, P2 Loses
            DB::table('users')->where('id', $player1Id)->increment('elo', $eloChange);
            DB::table('users')->where('id', $player2Id)->decrement('elo', $eloChange);

            TournamentEntry::where('user_id', $player1Id)->increment('total_elo_gain', $eloChange);
            TournamentEntry::where('user_id', $player2Id)->decrement('total_elo_gain', $eloChange);

        } elseif ($winner === 'player2') {
            // --- PLAYER 2 WINS ---
            // P2 Gains, P1 Loses
            DB::table('users')->where('id', $player1Id)->decrement('elo', $eloChange);
            DB::table('users')->where('id', $player2Id)->increment('elo', $eloChange);

            TournamentEntry::where('user_id', $player1Id)->decrement('total_elo_gain', $eloChange);
            TournamentEntry::where('user_id', $player2Id)->increment('total_elo_gain', $eloChange);

        } else {
            // --- DRAW LOGIC ---
            // Rule: The higher rated player loses points, the lower rated player gains them.
            
            if ($ratingA > $ratingB) {
                // Player 1 was the favorite (Expected > 0.5) but only got 0.5.
                // Result: Player 1 Loses, Player 2 Gains.
                DB::table('users')->where('id', $player1Id)->decrement('elo', $eloChange);
                DB::table('users')->where('id', $player2Id)->increment('elo', $eloChange);

                TournamentEntry::where('user_id', $player1Id)->decrement('total_elo_gain', $eloChange);
                TournamentEntry::where('user_id', $player2Id)->increment('total_elo_gain', $eloChange);

            } elseif ($ratingB > $ratingA) {
                // Player 2 was the favorite.
                // Result: Player 2 Loses, Player 1 Gains.
                DB::table('users')->where('id', $player1Id)->increment('elo', $eloChange);
                DB::table('users')->where('id', $player2Id)->decrement('elo', $eloChange);

                TournamentEntry::where('user_id', $player1Id)->increment('total_elo_gain', $eloChange);
                TournamentEntry::where('user_id', $player2Id)->decrement('total_elo_gain', $eloChange);
            }
            // If $ratingA === $ratingB, expected score is 0.5, actual is 0.5.
            // $eloChange will be 0, so no updates are needed (code falls through).
        }
    }
}