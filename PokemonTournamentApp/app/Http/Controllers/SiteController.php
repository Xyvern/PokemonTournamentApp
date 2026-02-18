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

    public function tournaments(Request $request)
    {
        // Start with a base query
        $query = Tournament::query();

        // Apply Filter based on the button clicked
        switch ($request->get('filter')) {
            case 'upcoming':
                // Show tournaments that haven't started or are currently active
                $query->where('status', 'registration')
                    ->orderBy('start_date', 'asc');
                break;
                
            case 'registered':
                // Show only tournaments the current user has joined
                $query->whereHas('entries', function ($q) {
                    $q->where('user_id', Auth::id());
                    $q->where('status', 'registration');
                })->orderByDesc('start_date');
                break;
                
            case 'completed':
                // Completed tournaments
                $query->where('status', 'completed')
                    ->orderByDesc('start_date');
                break;

            default:
                // "All" view (Default) - sort by newest first
                $query->orderByDesc('start_date');
                break;
        }

        // Paginate results (e.g., 12 per page)
        $tournaments = $query->paginate(12);

        return view('tournaments.index', compact('tournaments'));
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
        $myDeck = Deck::where('user_id', Auth::id())->get();
        $metaStats = $tournament->entries->map(function ($entry) {
            // Traverse relationships safely. If any part is null, default to 'Other / Rogue'
            return $entry->deck?->globalDeck?->archetype?->name ?? 'Other / Rogue';
        })->countBy()->sortDesc();

        // Prepare arrays for Chart.js
        $metaLabels = $metaStats->keys()->toArray();
        $metaData = $metaStats->values()->toArray();
        return view('tournaments.detail', compact(
            'tournament', 'currentRound', 'myEntry', 'matches', 
            'metaLabels', 'metaData', 'myDeck'
        ));
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

    // app/Http/Controllers/ArchetypeController.php

    public function archetypeDetail($id, Request $request)
    {
        // 1. Fetch Archetype
        $archetype = Archetype::with('keyCard')->findOrFail($id);

        // 2. Fetch "Latest Results"
        $query = TournamentEntry::query()
            ->join('tournaments', 'tournament_entries.tournament_id', '=', 'tournaments.id')
            ->where('tournaments.status', 'completed')
            ->whereHas('deck.globalDeck.archetype', function ($q) use ($id) {
                $q->where('archetypes.id', $id);
            })
            ->with(['tournament', 'user', 'deck'])
            ->orderByDesc('tournaments.start_date')
            ->orderBy('rank')
            ->select('tournament_entries.*');

        // 2. Conditional Limit
        if ($request->has('view_all')) {
            // If user clicked "See All", get EVERYTHING (No Pagination)
            $latestResults = $query->get(); 
        } else {
            // Default: Get only top 20
            $latestResults = $query->limit(20)->get();
        }

        // 3. Fetch "Player Statistics"
        $playerStats = TournamentEntry::query()
            ->join('tournaments', 'tournament_entries.tournament_id', '=', 'tournaments.id')
            ->where('tournaments.status', 'completed')
            ->whereHas('deck.globalDeck.archetype', function ($q) use ($id) {
                $q->where('archetypes.id', $id); // FIX: Explicit table name
            })
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($entries) {
                $wins = $entries->sum('wins');
                $losses = $entries->sum('losses');
                $ties = $entries->sum('ties');
                $total = $wins + $losses + $ties;
                
                return [
                    'user' => $entries->first()->user,
                    'total_matches' => $total,
                    'wins' => $wins,
                    'win_rate' => $total > 0 ? round(($wins / $total) * 100, 1) : 0,
                    'best_rank' => $entries->min('rank') ?? '-',
                    'entries_count' => $entries->count()
                ];
            })
            ->sortByDesc('win_rate');

        return view('archetypes.detail', compact('archetype', 'latestResults', 'playerStats'));
    }
}
            