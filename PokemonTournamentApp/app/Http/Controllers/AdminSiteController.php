<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Set;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminSiteController extends Controller
{
    /**
     * Displays the main admin dashboard with top-level metrics and graphical charts.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Top-Level Metrics
        $stats = [
            'total_players' => User::where('role', 1)->count(),
            // Count premium players where premium_until is in the future
            'premium_players' => User::where('role', 1)
                                     ->whereNotNull('premium_until')
                                     ->where('premium_until', '>', now())
                                     ->count(),
            'completed_tournaments' => Tournament::where('status', 'completed')->count(),
            'total_archetypes' => Archetype::count(),
            'total_decks' => Deck::count(), 
        ];

        // 2. Chart Data: Most Popular Archetypes (Top 5)
        $popularArchetypes = Archetype::orderBy('times_played', 'desc')
            ->take(5)
            ->get(['name', 'times_played']);
            
        $popularChartLabels = $popularArchetypes->pluck('name');
        $popularChartData = $popularArchetypes->pluck('times_played');

        // 3. Chart Data: Highest Win Rates (Minimum 5 games played)
        $winRateArchetypes = Archetype::where('times_played', '>=', 5)
            ->orderBy('wins', 'desc')
            ->get()
            ->sortByDesc(function($arch) {
                return $arch->times_played > 0 ? ($arch->wins / $arch->times_played) * 100 : 0;
            })
            ->take(5);

        $winRateLabels = $winRateArchetypes->pluck('name');
        $winRateData = $winRateArchetypes->map(function($arch) {
            return $arch->times_played > 0 ? round(($arch->wins / $arch->times_played) * 100) : 0;
        })->values();

        // 4. Chart Data: Tournament Attendance
        $recentTournaments = Tournament::where('status', 'completed')
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get()
            ->reverse()
            ->values();

        $attendanceLabels = $recentTournaments->pluck('name')->map(function($name) {
            return Str::limit($name, 15);
        });
        $attendanceData = $recentTournaments->pluck('registered_player');

        // 5. NEW: Recent Transactions (Latest 10)
        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // 6. NEW: Player Competitive Report (Top 15 Players)
        // 1. Get sorting parameters from the URL (Default to ELO descending)
        $sortBy = request('sort', 'elo'); 
        $sortDir = request('dir', 'desc');

        // 2. Build the base query
        $query = User::where('role', 1)
            ->withCount('tournamentEntries as total_sessions')
            ->withCount(['tournamentEntries as rank_1_count' => function ($q) {
                $q->where('rank', 1);
            }])
            ->withMax('tournamentEntries', 'created_at');

        // 3. Apply the correct database sorting BEFORE taking 15
        if ($sortBy === 'last_active') {
            $query->orderBy('tournament_entries_max_created_at', $sortDir);
        } elseif ($sortBy === 'sessions') {
            $query->orderBy('total_sessions', $sortDir);
        } elseif ($sortBy === 'rank_1') {
            $query->orderBy('rank_1_count', $sortDir);
        } elseif ($sortBy === 'name') {
            $query->orderBy('nickname', $sortDir);
        } else {
            $query->orderBy('elo', $sortDir); // Default
        }

        // 4. Finally, grab the top 15 of whatever was sorted
        $playerReports = $query->take(15)->get();

        $activeTournaments = Tournament::where('status', 'active')
            ->orderBy('start_date', 'asc')
            ->take(3)
            ->get();

        $upcomingTournaments = Tournament::where('status', 'registration')
            ->orderBy('start_date', 'asc')
            ->take(3)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 
            'popularChartLabels', 'popularChartData', 
            'winRateLabels', 'winRateData', 
            'attendanceLabels', 'attendanceData',
            'recentTransactions', 'playerReports',
            'activeTournaments', 'upcomingTournaments' // Updated variable here!
        ));
    }

    /**
     * Displays a paginated list of tournaments, optionally filtered by status.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function tournaments(Request $request)
    {
        $query = Tournament::query();

        switch ($request->get('filter')) {
            case 'upcoming':
                $query->where('status', 'registration')
                    ->orderBy('start_date', 'asc');
                break;
                
            case 'completed':
                $query->where('status', 'completed')
                    ->orderByDesc('start_date');
                break;

            case 'active':
                $query->where('status', 'active')
                    ->orderBy('start_date', 'asc');
                break;

            default:
                $query->orderByDesc('start_date');
                break;
        }

        $tournaments = $query->get();

        return view('admin.tournaments.index', compact('tournaments'));
    }

    /**
     * Displays the tournament management console, including pairings, standings, and meta statistics.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function tournamentDetail($id)
    {
        $tournament = Tournament::findOrFail($id);
        
        $myEntry = TournamentEntry::where('tournament_id', $tournament->id)
            ->where('user_id', Auth::id())
            ->first();
            
        if ($tournament->status == "active") {
            $currentRound = $tournament->matches->max('round_number');
        } else {
            $currentRound = 1;
        }
        
        $matches = $tournament->getMatchesForRound($currentRound);
        $myDeck = Deck::where('user_id', Auth::id())->get();
        
        $metaStats = $tournament->entries->map(function ($entry) {
            return $entry->deck?->globalDeck?->archetype?->name ?? 'Other / Rogue';
        })->countBy()->sortDesc();

        $metaLabels = $metaStats->keys()->toArray();
        $metaData = $metaStats->values()->toArray();
        
        return view('admin.tournaments.detail', compact(
            'tournament', 'currentRound', 'myEntry', 'matches', 
            'metaLabels', 'metaData', 'myDeck'
        ));
    }

    /**
     * Displays the card database with set pagination and search functionality.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function cardDatabase(Request $request)
    {
        $latestSet = Set::orderBy('release_date', 'desc')->first();

        $setsQuery = Set::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            
            $setsQuery->whereHas('cards', function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });

            $setsQuery->with(['cards' => function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orderByRaw('CAST(number AS UNSIGNED) ASC');
            }]);
        } else {
            $setsQuery->with(['cards' => function ($query) {
                $query->orderByRaw('CAST(number AS UNSIGNED) ASC'); 
            }]);
        }

        $sets = $setsQuery->orderBy('release_date', 'desc')->paginate(2);

        if ($request->filled('search')) {
            $sets->appends(['search' => $request->search]);
        }

        return view('admin.cards.index', compact('latestSet', 'sets'));
    }

    /**
     * Synchronizes the local database with the official Pokémon TCG API.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncFromApi()
    {
        set_time_limit(0);

        $latestSet = Set::orderBy('release_date', 'desc')->first();
        
        $apiQuery = '';
        if ($latestSet && $latestSet->release_date) {
            $formattedDate = Carbon::parse($latestSet->release_date)->format('Y/m/d');
            $apiQuery = "?q=releaseDate:>{$formattedDate}&orderBy=releaseDate";
        }

        $setsResponse = Http::get("https://api.pokemontcg.io/v2/sets{$apiQuery}");
        
        if (!$setsResponse->successful()) {
            return back()->with('error', 'Failed to connect to Pokemon TCG API for Sets.');
        }

        $newSets = $setsResponse->json()['data'];

        if (empty($newSets)) {
            return back()->with('success', 'Your database is already up to date!');
        }

        $cardsAdded = 0;

        DB::transaction(function () use ($newSets, &$cardsAdded) {
            foreach ($newSets as $setData) {
                $set = Set::updateOrCreate(
                    ['api_id' => $setData['id']],
                    [
                        'name' => $setData['name'],
                        'ptcgo_code' => $setData['ptcgoCode'] ?? null,
                        'release_date' => $setData['releaseDate'],
                    ]
                );

                $cardsResponse = Http::get("https://api.pokemontcg.io/v2/cards?q=set.id:{$setData['id']}");
                
                if ($cardsResponse->successful()) {
                    $cardsData = $cardsResponse->json()['data'];
                    
                    $cardsToInsert = [];
                    foreach ($cardsData as $cardData) {
                        $cardsToInsert[] = [
                            'api_id'      => $cardData['id'],
                            'set_id'      => $set->id,
                            'name'        => $cardData['name'],
                            'supertype'   => $cardData['supertype'],
                            'number'      => $cardData['number'],
                            'artist'      => $cardData['artist'] ?? 'Unknown',
                            'hp'          => $cardData['hp'] ?? null,
                            'is_playable' => true,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];
                    }

                    if (!empty($cardsToInsert)) {
                        foreach (array_chunk($cardsToInsert, 500) as $chunk) {
                            Card::insert($chunk);
                        }
                        $cardsAdded += count($cardsToInsert);
                    }
                }
            }
        });

        return back()->with('success', "Successfully synced " . count($newSets) . " new sets and {$cardsAdded} new cards!");
    }

    /**
     * Displays a paginated list of tournament entries that have unassigned deck archetypes.
     *
     * @return \Illuminate\View\View
     */
    public function unassignedDecks()
    {
        $entries = TournamentEntry::with(['tournament', 'user', 'deck.globalDeck.contents.card'])
            ->whereHas('deck.globalDeck', function ($query) {
                $query->whereNull('archetype_id');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $archetypes = Archetype::orderBy('name', 'asc')->get();

        return view('admin.unassigned_decks', compact('entries', 'archetypes'));
    }

    /**
     * Displays the form to create a new tournament.
     *
     * @return \Illuminate\View\View
     */
    public function createTournament()
    {
        return view('admin.tournaments.create');
    }

    /**
     * Displays a paginated list of all players for administration, including deactivated accounts.
     *
     * @return \Illuminate\View\View
     */
    public function managePlayers()
    {
        $players = User::where('role', 1)
            ->withTrashed()
            ->orderBy('elo', 'desc')
            ->paginate(40);

        return view('admin.players.index', compact('players'));
    }

    /**
     * Displays a paginated list of all deck archetypes.
     *
     * @return \Illuminate\View\View
     */
    public function archetypes()
    {
        $archetypes = Archetype::with('keyCard')->orderBy('name', 'asc')->paginate(24);

        return view('admin.archetypes.index', compact('archetypes'));
    }

    /**
     * Displays the detailed performance view of a specific archetype.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function archetypeDetail(Request $request, $id)
    {
        $archetype = Archetype::with('keyCard')->findOrFail($id);
        
        $globalWinRate = $archetype->times_played > 0 
            ? round(($archetype->wins / $archetype->times_played) * 100) 
            : 0;
        $archetype->win_rate = $globalWinRate;

        $resultsQuery = TournamentEntry::with(['tournament', 'user', 'deck.globalDeck.archetype'])
            ->whereHas('deck.globalDeck', function($q) use ($id) {
                $q->where('archetype_id', $id);
            })
            ->whereHas('tournament', function($q) {
                $q->where('status', 'completed');
            })
            ->orderByDesc('created_at');

        if ($request->has('view_all')) {
            $latestResults = $resultsQuery->get();
        } else {
            $latestResults = $resultsQuery->take(20)->get();
        }

        $allEntries = TournamentEntry::with('user')
            ->whereHas('deck.globalDeck', function($q) use ($id) {
                $q->where('archetype_id', $id);
            })
            ->whereHas('tournament', function($q) {
                $q->where('status', 'completed');
            })
            ->get();

        $playerStats = $allEntries->groupBy('user_id')->map(function ($entries) {
            $user = $entries->first()->user;
            $wins = $entries->sum('wins');
            $losses = $entries->sum('losses');
            $ties = $entries->sum('ties');
            
            $totalMatches = $wins + $losses + $ties;
            $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100) : 0;

            return [
                'user' => $user,
                'entries_count' => $entries->count(),
                'total_matches' => $totalMatches,
                'wins' => $wins,
                'win_rate' => $winRate,
            ];
        })->sortByDesc('win_rate')->values();

        return view('admin.archetypes.detail', compact('archetype', 'latestResults', 'playerStats'));
    }

    /**
     * Displays the detailed view of a specific card.
     *
     * @param string $id
     * @return \Illuminate\View\View
     */
    public function cardDetail($id)
    {
        $card = Card::where('api_id', $id)->firstOrFail();
        
        return view('admin.cards.detail', ['card' => $card]);
    }

    /**
     * Displays the form to edit an existing tournament's configuration.
     *
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editTournament($id)
    {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->status === 'completed') {
            return redirect()->route('admin.tournaments.detail', $id)
                            ->withErrors(['error' => 'Completed tournaments cannot be edited.']);
        }

        return view('admin.tournaments.edit', compact('tournament'));
    }
}