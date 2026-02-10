<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function cards()
    {
        $cards = Card::all();
        return view('cards.index', ['cards' => $cards]);
    }

    public function cardDetail($id)
    {
        $card = Card::where('api_id', $id)->firstOrFail();
        return view('cards.detail', ['card' => $card]);
    }

    public function tournaments()
    {
        $tournaments = Tournament::all();
        return view('tournaments.index', ['tournaments' => $tournaments]);
    }

    public function tournamentDetail($id)
    {
        $tournament = Tournament::findOrFail($id);
        $myEntry = TournamentEntry::where('tournament_id', $tournament->id)
        ->where('user_id', Auth::id())
        ->first();
        if ($tournament->status == "active") {
            $currentRound = $tournament->matches->max('round_number');
        }else{
            $currentRound = 1;
        }
        $matches = $tournament->getMatchesForRound($currentRound);
        return view('tournaments.detail', ['tournament' => $tournament, 'myEntry' => $myEntry, 'currentRound' => $currentRound, 'matches' => $matches]);
    }

    public function archetypes()
    {
        $archetypes = Archetype::all();
        return view('archetypes.index', ['archetypes' => $archetypes]);
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
