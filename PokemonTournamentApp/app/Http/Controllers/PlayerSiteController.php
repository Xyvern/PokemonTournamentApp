<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use App\Models\GlobalDeck;
use App\Models\Set;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerSiteController extends Controller
{
    public function playerHome()
    {
        $sets = Set::orderBy('release_date', 'desc')->take(4)->get();
        return view('player.home', compact('sets'));
    }

    public function leaderboard()
    {
        $players = User::select('*')
            ->orderByDesc('elo')
            ->orderByDesc(DB::raw('CASE WHEN matches_played > 0 THEN matches_won / matches_played ELSE 0 END'))
            ->orderByDesc('matches_played')
            ->get();
        return view('player.leaderboard', compact('players'));
    }

    public function playerProfile($id)
    {
        $player = User::find($id);
        if (!$player) {
            return redirect()->route('player.home')->with('error', 'Player not found.');
        }else{
            // $swissResults = SwissResult::where('userID', $id)->get();
            return view('player.profile', compact('player', 'swissResults'));
        }
    }

    public function myDecks()
    {
        $user = Auth::user();
        $decks = Deck::where('user_id', $user->id)->get();
        return view('player.mydecks', compact('decks'));
    }

    public function createDeck()
    {
        $cards = Card::all();
        return view('player.decks.create', compact('cards'));
    }

    public function playerSets()
    {
        $sets = Set::orderBy('release_date', 'desc')->get();
        return view('player.sets.index', compact('sets'));
    }

    public function playerSetDetail($id)
    {
        $set = Set::find($id);
        $cards = Card::where('set_id', $id)->orderByRaw("CAST(number AS UNSIGNED), number")->get();
        if (!$set) {
            return redirect()->route('player.sets.index')->with('error', 'Set not found.');
        }
        return view('player.sets.detail', compact('set', 'cards'));
    }

    public function showDeck($id)
    {
        $deck = Deck::with('globalDeck.cards')->findOrFail($id);
        if (!$deck) {
            return redirect()->route('player.mydecks')->with('error', 'Deck not found.');
        }
        return view('player.decks.show', compact('deck'));
    }
}
