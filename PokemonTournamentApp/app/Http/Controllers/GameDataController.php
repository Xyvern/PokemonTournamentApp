<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameDataController extends Controller
{
    public function getPlayersData()
    {
        // Mocking Player 1 Data
        $player1 = [
            'id' => 101,
            'username' => 'HeroPlayer',
            'health' => 100,
            'deck' => [
                ['card_id' => 'c_001', 'name' => 'Fire Dragon', 'power' => 80],
                ['card_id' => 'c_002', 'name' => 'Shield Wall', 'power' => 0],
                ['card_id' => 'c_003', 'name' => 'Potion', 'power' => 0],
            ]
        ];

        // Mocking Player 2 Data
        $player2 = [
            'id' => 202,
            'username' => 'RivalPlayer',
            'health' => 100,
            'deck' => [
                ['card_id' => 'c_004', 'name' => 'Ice Wizard', 'power' => 70],
                ['card_id' => 'c_005', 'name' => 'Lightning Bolt', 'power' => 50],
            ]
        ];

        // Combine into a single response structure
        $responseData = [
            'match_id' => 'm_998877',
            'player_1' => $player1,
            'player_2' => $player2
        ];

        // Return as JSON
        return response()->json($responseData);
    }
}
