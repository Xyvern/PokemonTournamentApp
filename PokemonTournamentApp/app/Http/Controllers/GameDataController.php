<?php

namespace App\Http\Controllers;

use App\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $request->validate([
            'match_id' => 'required|exists:tournament_matches,id',
            'result_code' => 'required|in:1,2,3',
        ]);

        $matchId = $request->input('match_id');
        $resultCode = $request->input('result_code');

        $updated = TournamentMatch::where('id', $matchId)->update([
            'result_code' => $resultCode,
        ]);

        if ($updated) {
            return response()->json([
                'status' => 'success',
                'message' => 'Match data stored successfully'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Match data storage failed'
        ], 400);
    }

    public function photonWebhook(Request $request)
    {
        $type = $request->input('Type');
        $userId = $request->input('UserId');
        
        $connectedUsers = Cache::get('photon_connected_users', []);

        if ($type === 'Join') {
            // Track the user ID if provided
            if ($userId && !in_array($userId, $connectedUsers)) {
                $connectedUsers[] = $userId;
            }
        } elseif ($type === 'Leave') {
            if ($userId) {
                $connectedUsers = array_values(array_diff($connectedUsers, [$userId]));
            }
        }

        Cache::put('photon_connected_users', $connectedUsers);
        Cache::put('photon_current_ccu', count($connectedUsers));

        return response()->json([
            'ResultCode' => 0,
            'Message' => 'Success'
        ]);
    }
}
