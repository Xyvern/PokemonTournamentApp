<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\GlobalDeck;
use App\Models\Set;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerSiteController extends Controller
{
    public function playerHome()
    {
        $archetypes = Archetype::all();
        $sets = Set::orderBy('release_date', 'desc')->take(4)->get();
        $recentTournaments = Tournament::where('status', 'completed')
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        // 2. Query for Upcoming Tournaments (Registration or Active)
        // Ordered by soonest start_date first
        $upcomingTournaments = Tournament::where('status', 'registration')
            ->orderBy('start_date', 'asc')
            ->take(3)
            ->get();

        // 3. Query for Current Tournaments the Player is Registered In
        $activeTournaments = Tournament::where('status', 'active')
            ->orderBy('start_date', 'asc')
            ->get();

        $currentTournaments = [];
        foreach ($activeTournaments as $tournament) {
            if ($tournament->entries()->where('user_id', Auth::id())->exists()) {
                $currentTournaments[] = $tournament; 
            }
        }
        // 4. Get All Upcoming Tournaments for Registration Check
        $allUpcomingTournaments = Tournament::where('status', 'registration')
            ->orderBy('start_date', 'asc')
            ->get();
        $registeredTournaments = [];
        foreach ($allUpcomingTournaments as $tournament) {
            if ($tournament->entries()->where('user_id', Auth::id())->exists()){
                $registeredTournaments[] = $tournament;
            }
        }
        // 3. DUMMY DATA GENERATOR (For visual testing if DB is empty)
        // This ensures your Blade doesn't error or show empty states while developing.
        if ($upcomingTournaments->isEmpty()) {
            $upcomingTournaments = collect([
                (object)[
                    'id' => 999,
                    'name' => 'Weekly Cup (Dummy)',
                    'capacity' => 64,
                    'registered_player' => 12,
                    'start_date' => now()->addDays(2), // 2 days from now
                    'status' => 'registration',
                    'format' => 'Standard'
                ],
                (object)[
                    'id' => 1000,
                    'name' => 'Championship (Dummy)',
                    'capacity' => 128,
                    'registered_player' => 45,
                    'start_date' => now()->addDays(5),
                    'status' => 'registration',
                    'format' => 'Standard'
                ]
            ]);
        }
        return view('player.home', compact('sets', 'archetypes', 'recentTournaments', 'upcomingTournaments', 'currentTournaments', 'registeredTournaments'));
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
