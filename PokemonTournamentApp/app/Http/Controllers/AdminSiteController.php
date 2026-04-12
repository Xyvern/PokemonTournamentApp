<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Set;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AdminSiteController extends Controller
{
    public function index()
    {
        // 1. Top-Level Metrics
        $stats = [
            'total_players' => User::where('role', 1)->count(),
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

        // 3. Chart Data: Highest Win Rates (Minimum 5 games played to filter outliers)
        $winRateArchetypes = Archetype::where('times_played', '>=', 5)
            ->orderBy('wins', 'desc') // Assuming you calculate win_rate on the fly, otherwise order by 'win_rate'
            ->get()
            ->sortByDesc(function($arch) {
                return $arch->times_played > 0 ? ($arch->wins / $arch->times_played) * 100 : 0;
            })
            ->take(5);

        $winRateLabels = $winRateArchetypes->pluck('name');
        $winRateData = $winRateArchetypes->map(function($arch) {
            return $arch->times_played > 0 ? round(($arch->wins / $arch->times_played) * 100) : 0;
        })->values();

        // 4. Chart Data: Tournament Attendance (Last 6 Tournaments)
        $recentTournaments = Tournament::where('status', 'completed')
            ->orderBy('start_date', 'desc')
            ->take(6)
            ->get()
            ->reverse() // Reverse so the oldest is on the left of the chart
            ->values();

        $attendanceLabels = $recentTournaments->pluck('name')->map(function($name) {
            return \Illuminate\Support\Str::limit($name, 15); // Truncate long names for the chart
        });
        $attendanceData = $recentTournaments->pluck('registered_player');

        return view('admin.dashboard', compact(
            'stats', 
            'popularChartLabels', 'popularChartData', 
            'winRateLabels', 'winRateData', 
            'attendanceLabels', 'attendanceData'
        ));
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
        $tournaments = $query->get();

        return view('admin.tournaments.index', compact('tournaments'));
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
        return view('admin.tournaments.detail', compact(
            'tournament', 'currentRound', 'myEntry', 'matches', 
            'metaLabels', 'metaData', 'myDeck'
        ));
    }

    public function cardDatabase()
    {
        // 1. Get the latest set for the header panel
        $latestSet = Set::orderBy('release_date', 'desc')->first();

        // 2. Paginate by SET (3 at a time), and eager load their cards
        // We order the sets from newest to oldest, and the cards inside them by their set number
        $sets = Set::with(['cards' => function ($query) {
                // Ordering by the integer value of the number ensures card "2" comes before "10"
                $query->orderByRaw('CAST(number AS UNSIGNED) ASC'); 
            }])
            ->orderBy('release_date', 'desc')
            ->paginate(2);

        return view('admin.cards.index', compact('latestSet', 'sets'));
    }

    /**
     * Pull new Sets and Cards from the Pokemon TCG API.
     */
    public function syncFromApi()
    {
        set_time_limit(0); // Prevent timeout during large API pulls

        // 1. Find the latest release date in your DB
        $latestSet = Set::orderBy('release_date', 'desc')->first();
        
        // Format the API query. If DB is empty, pull everything. Otherwise, pull newer.
        // The API expects format YYYY/MM/DD
        $apiQuery = '';
        if ($latestSet && $latestSet->release_date) {
            $formattedDate = Carbon::parse($latestSet->release_date)->format('Y/m/d');
            $apiQuery = "?q=releaseDate:>{$formattedDate}&orderBy=releaseDate";
        }

        // 2. Fetch New Sets
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
                // A. Insert the Set
                $set = Set::updateOrCreate(
                    ['api_id' => $setData['id']], // Assuming your sets table uses api_id
                    [
                        'name' => $setData['name'],
                        'ptcgo_code' => $setData['ptcgoCode'] ?? null,
                        'release_date' => $setData['releaseDate'],
                        // Add other set columns you track here
                    ]
                );

                // B. Fetch Cards for this specific Set
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
                            'is_playable' => true, // Apply your custom logic here if needed
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];
                    }

                    // Bulk insert the cards for speed
                    if (!empty($cardsToInsert)) {
                        // Chunking to avoid massive SQL query limits
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

    public function unassignedDecks()
    {
        // Get all entries where the GlobalDeck doesn't have an archetype yet
        $entries = TournamentEntry::with(['tournament', 'user', 'deck.globalDeck.contents.card'])
            ->whereHas('deck.globalDeck', function ($query) {
                $query->whereNull('archetype_id');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Fetch archetypes for the dropdown
        $archetypes = Archetype::orderBy('name', 'asc')->get();

        return view('admin.unassigned_decks', compact('entries', 'archetypes'));
    }

    public function createTournament()
    {
        return view('admin.tournaments.create');
    }

    public function managePlayers()
    {
        // Fetch all players (Role 1), including deactivated ones, 40 per page
        $players = User::where('role', 1)
            ->withTrashed()
            ->orderBy('elo', 'desc')
            ->paginate(40);

        return view('admin.players.index', compact('players'));
    }

    public function archetypes()
    {
        // Fetch all archetypes, paginated
        $archetypes = Archetype::with('keyCard')->orderBy('name', 'asc')->paginate(24);

        return view('admin.archetypes.index', compact('archetypes'));
    }

    public function archetypeDetail(Request $request, $id)
    {
        $archetype = Archetype::with('keyCard')->findOrFail($id);
        
        // Calculate Global Win Rate safely
        $globalWinRate = $archetype->times_played > 0 
            ? round(($archetype->wins / $archetype->times_played) * 100) 
            : 0;
        $archetype->win_rate = $globalWinRate;

        // 1. Fetch Latest Results (Completed Tournaments Only)
        $resultsQuery = TournamentEntry::with(['tournament', 'user', 'deck.globalDeck.archetype'])
            ->whereHas('deck.globalDeck', function($q) use ($id) {
                $q->where('archetype_id', $id);
            })
            ->whereHas('tournament', function($q) {
                $q->where('status', 'completed');
            })
            ->orderByDesc('created_at');

        // Handle the "Show All" toggle
        if ($request->has('view_all')) {
            $latestResults = $resultsQuery->get();
        } else {
            $latestResults = $resultsQuery->take(20)->get();
        }

        // 2. Fetch Player Statistics for this Archetype
        $allEntries = TournamentEntry::with('user')
            ->whereHas('deck.globalDeck', function($q) use ($id) {
                $q->where('archetype_id', $id);
            })
            ->whereHas('tournament', function($q) {
                $q->where('status', 'completed');
            })
            ->get();

        // Group by User and calculate individual win rates
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
        })->sortByDesc('win_rate')->values(); // Sort so the best players are at the top

        return view('admin.archetypes.detail', compact('archetype', 'latestResults', 'playerStats'));
    }
}
