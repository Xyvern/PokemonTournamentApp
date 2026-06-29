<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\GlobalDeck;
use App\Models\Set;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function cards(Request $request)
    {
        $search = $request->input('search');
        $supertype = $request->input('supertype');
        $sortCards = $request->input('sort_cards', 'number_asc');

        $query = Set::query();

        if ($search || $supertype) {
            $query->whereHas('cards', function ($q) use ($search, $supertype) {
                if ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%');
                }
                if ($supertype) {
                    $q->where('supertype', $supertype);
                }
            });

            $query->with(['cards' => function ($q) use ($search, $supertype, $sortCards) {
                if ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%');
                }
                if ($supertype) {
                    $q->where('supertype', $supertype);
                }
                if ($sortCards == 'name_asc') {
                    $q->orderBy('name', 'asc');
                } elseif ($sortCards == 'name_desc') {
                    $q->orderBy('name', 'desc');
                } else {
                    $q->orderByRaw('CAST(number AS UNSIGNED) ASC');
                }
            }]);
        } else {
            $query->with(['cards' => function ($q) use ($sortCards) {
                if ($sortCards == 'name_asc') {
                    $q->orderBy('name', 'asc');
                } elseif ($sortCards == 'name_desc') {
                    $q->orderBy('name', 'desc');
                } else {
                    $q->orderByRaw('CAST(number AS UNSIGNED) ASC');
                }
            }]);
        }

        $sets = $query->orderBy('release_date', 'desc')->paginate(2);

        // Remember query parameters for pagination
        $sets->appends($request->query());

        return view('cards.index', compact('sets')); // Adjust view path if needed
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

                case 'active':
                // Active tournaments
                $query->where('status', 'active')
                    ->orderBy('start_date', 'asc');
                break;

            default:
                // "All" view (Default) - sort by newest first
                $query->orderByDesc('start_date');
                break;
        }

        // THE FIX: Fetch all matching records so DataTables can handle the pagination
        $tournaments = $query->get();

        return view('tournaments.index', compact('tournaments'));
    }

    public function tournamentDetail($id)
    {
        $tournament = Tournament::findOrFail($id);

        // 1. Initialize variables safely for guests
        $myEntry = null;
        $myDeck = collect(); 

        // 2. Only query personal data if a user is logged in
        if (Auth::check()) {
            $myEntry = TournamentEntry::where('tournament_id', $tournament->id)
                ->where('user_id', Auth::id())
                ->first();
                
            $myDeck = Deck::where('user_id', Auth::id())->get();
        }

        // 3. Tournament match logic (Viewable by everyone)
        if ($tournament->status == "active") {
            // Added ?? 1 just in case max() returns null when no matches exist yet
            $currentRound = $tournament->matches->max('round_number') ?? 1; 
        } else {
            $currentRound = 1;
        }
        
        $matches = $tournament->getMatchesForRound($currentRound);

        // 4. Metagame chart logic (Viewable by everyone)
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
        $deckHistory = GlobalDeck::with(['decks.tournamentEntries.tournament'])
            ->where('id', $deck->global_deck_id)
            ->first();
        // dd($deckHistory->decks[1]->tournamentEntries);
        return view('player.decks.show', compact('deck', 'deckHistory'));
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
                        // Calculate the Wilson Score here:
                        'wilson_score' => $this->calculateWilsonScore($wins, $total),
                        'best_rank' => $entries->min('rank') ?? '-',
                        'entries_count' => $entries->count()
                    ];
                })
                ->sortByDesc('wilson_score')
                ->take(10);
        return view('archetypes.detail', compact('archetype', 'latestResults', 'playerStats'));
    }

    // Helper function
    /**
     * Calculates the Wilson Score Interval Lower Bound
     * @param int $wins The number of successful outcomes
     * @param int $total The total number of attempts/matches
     * @return float The lower bound score (0.0 to 1.0)
     */
    public function calculateWilsonScore($wins, $total)
    {
        if ($total == 0) {
            return 0.0;
        }

        $z = 1.96; // 95% confidence level
        $phat = $wins / $total;

        // Numerator calculations
        $term1 = $phat + ($z * $z) / (2 * $total);
        $term2 = $z * sqrt(($phat * (1 - $phat) + ($z * $z) / (4 * $total)) / $total);
        $numerator = $term1 - $term2;

        // Denominator calculation
        $denominator = 1 + ($z * $z) / $total;

        return $numerator / $denominator;
    }
}