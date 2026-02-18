<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\GlobalDeck;
use App\Models\Set;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerSiteController extends Controller
{
    public function playerHome()
    {
        $archetypes = Archetype::orderBy('times_played', 'desc')
            ->take(4)
            ->get();
        $sets = Set::orderBy('release_date', 'desc')
            ->take(4)
            ->get();
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
            ->take(4)
            ->get();
        $registeredTournaments = [];
        foreach ($allUpcomingTournaments as $tournament) {
            if ($tournament->entries()->where('user_id', Auth::id())->exists()){
                $registeredTournaments[] = $tournament;
            }
        }
        return view('player.home', compact('sets', 'archetypes', 'recentTournaments', 'upcomingTournaments', 'currentTournaments', 'registeredTournaments'));
    }

    public function leaderboard(Request $request)
    {
        // Fetch players sorted by Elo -> Winrate -> Matches
        $players = User::select('*')
            ->orderByDesc('elo')
            ->orderByDesc(DB::raw('CASE WHEN matches_played > 0 THEN matches_won / matches_played ELSE 0 END'))
            ->orderByDesc('matches_played')
            ->get();

        // If the request comes from our JavaScript, return ONLY the table rows HTML
        if ($request->ajax()) {
            return view('player.leaderboardrow', compact('players'))->render();
        }

        // Otherwise, load the full page
        return view('player.leaderboard', compact('players'));
    }

    public function playerProfile($id)
    {
        $user = User::findOrFail($id);
        $isOwnProfile = Auth::check() && Auth::id() === $user->id;

        // --- 1. Leaderboard Rank ---
        // Count users with higher ELO + 1 to get the rank (e.g., if 0 people are higher, you are #1)
        $leaderboardRank = User::where('elo', '>', $user->elo)->count() + 1;

        // --- Helper: Completed Tournament Filter ---
        $completedFilter = function ($query) {
            $query->where('status', 'completed');
        };

        // --- 2. Basic Stats (Only Completed Tournaments) ---
        $tournamentsJoined = TournamentEntry::where('user_id', $id)
            ->whereHas('tournament', $completedFilter)
            ->count();

        $bestFinish = TournamentEntry::where('user_id', $id)
            ->whereHas('tournament', $completedFilter)
            ->whereNotNull('rank')
            ->min('rank');

        $averageRank = TournamentEntry::where('user_id', $id)
            ->whereHas('tournament', $completedFilter)
            ->whereNotNull('rank')
            ->avg('rank');

        // --- 3. Win/Loss Record ---
        $record = TournamentEntry::where('user_id', $id)
            ->whereHas('tournament', $completedFilter)
            ->selectRaw('SUM(wins) as wins, SUM(losses) as losses, SUM(ties) as ties')
            ->first();

        $totalWins = $record->wins ?? 0;
        $totalLosses = $record->losses ?? 0;
        $totalTies = $record->ties ?? 0;
        
        $totalGames = $totalWins + $totalLosses + $totalTies;
        $matchesPlayed = $totalGames; // Using calculated total for consistency

        $safeTotal = $totalGames ?: 1; 
        $winPct = round(($totalWins / $safeTotal) * 100, 1);
        $lossPct = round(($totalLosses / $safeTotal) * 100, 1);
        $tiePct = round(($totalTies / $safeTotal) * 100, 1);

        // --- 4. Signature Archetype & Best Deck ---
        $completedEntries = TournamentEntry::where('user_id', $id)
            ->whereHas('tournament', $completedFilter)
            ->with(['deck.globalDeck.archetype.keyCard.images'])
            ->get();

        $archetypeStats = $completedEntries->groupBy(function($entry) {
            return $entry->deck->globalDeck->archetype->id ?? 0;
        })->reject(function($group, $key) {
            return $key === 0;
        });

        // Signature (Most Played)
        $signatureGroup = $archetypeStats->sortByDesc(function($group) {
            return $group->count();
        })->first();

        $signatureArchetype = null;
        $signatureCount = 0;
        
        if ($signatureGroup) {
            $signatureArchetype = $signatureGroup->first()->deck->globalDeck->archetype;
            $signatureCount = $signatureGroup->count();
        }

        // Best Winrate (Min 3 Games)
        $bestDeckGroup = $archetypeStats->map(function($group) {
            $wins = $group->sum('wins');
            $losses = $group->sum('losses');
            $ties = $group->sum('ties');
            $total = $wins + $losses + $ties;
            
            return [
                'archetype' => $group->first()->deck->globalDeck->archetype,
                'total_wins' => $wins,
                'total_games' => $total,
                'win_rate' => $total > 0 ? $wins / $total : 0
            ];
        })->filter(function($stats) {
            return $stats['total_games'] >= 3; 
        })->sortByDesc('win_rate')->first();

        $bestDeck = null;
        if ($bestDeckGroup) {
            $bestDeck = $bestDeckGroup['archetype'];
            $bestDeck->total_wins = $bestDeckGroup['total_wins'];
            $bestDeck->total_games = $bestDeckGroup['total_games'];
            $bestDeck->calculated_win_rate = round($bestDeckGroup['win_rate'] * 100, 1);
        }

        // --- 5. Latest Results ---
        $latestResults = TournamentEntry::where('user_id', $id)
            ->with(['tournament', 'deck.globalDeck.archetype'])
            ->join('tournaments', 'tournament_entries.tournament_id', '=', 'tournaments.id')
            ->where('tournaments.status', 'completed')
            ->orderByDesc('tournaments.start_date')
            ->select('tournament_entries.*')
            ->limit(10)
            ->get();

        return view('player.profile', compact(
            'user', 'isOwnProfile', 'leaderboardRank', 'matchesPlayed', 'tournamentsJoined', 
            'bestFinish', 'averageRank', 'totalWins', 'totalLosses', 'totalTies',
            'winPct', 'lossPct', 'tiePct', 'signatureArchetype', 'signatureCount', 
            'bestDeck', 'latestResults'
        ));
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
