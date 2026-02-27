<?php

namespace App\Http\Controllers;

use App\Models\TournamentMatch;
use Illuminate\Http\Request;

class GameDataController extends Controller
{
    public function getMatchData($matchId)
    {
        $match = TournamentMatch::with([
            'player1.deck.globalDeck.cards', 
            'player2.deck.globalDeck.cards'
        ])->find($matchId);

        if (!$match) {
            return response()->json(['error' => 'Match not found'], 404);
        }

        $getDeckList = function ($entry) {
            if (!$entry || !$entry->deck || !$entry->deck->globalDeck) {
                return []; 
            }

            return $entry->deck->globalDeck->cards->map(function ($card) {
                return [
                    'api_id' => $card->api_id,
                    'qty'    => $card->pivot->quantity,
                ];
            });
        };

        $player1 = $match->player1;
        $player2 = $match->player2;

        return response()->json([
            'matchData' => [
                'tournament_id' => $match->tournament_id,
                'round_number'  => $match->round_number,
                'room_code'     => $match->room_code,
                'starting_player' => $match->starting_player,
            ],
            'players' => [
                'player1' => [
                    'id' => $player1?->user->id,
                    'nickname'  => $player1?->user->nickname,
                    'elo' => $player1?->user->elo,
                    'deck'     => $getDeckList($player1), 
                ],
                'player2' => [
                    'id' => $player2?->user->id,
                    'nickname'  => $player2?->user->nickname,
                    'elo' => $player2?->user->elo,
                    'deck'     => $getDeckList($player2), 
                ],
            ]
        ]);
    }

    public function storeMatchData(Request $request)
    {
        $resultCode = $request->input('result_code');
        $matchId = $request->input('match_id');
        if ($resultCode==3) {
            TournamentMatch::where('id', $matchId)
                ->update([
                    'result_code' => $resultCode,
                ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Match data stored successfully'
            ]);
        }else{
            TournamentMatch::where('id', $matchId)
                ->update([
                    'result_code' => $resultCode,
                ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Match data stored successfully'
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Match data storage failed'
        ]);
    }
}
