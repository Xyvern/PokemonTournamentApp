<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\Card;
use App\Models\GlobalDeck;
use App\Models\Set;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\ArchetypeService;
use App\Services\EloCalculator;
use Illuminate\Http\Request;

use App\Services\SwissPairingGenerator;
use App\Services\TournamentServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function updateMatchResult(Request $request, $id)
    {
        $request->validate([
            'match_id' => 'required|exists:tournament_matches,id',
            'result_code' => 'nullable|in:1,2,3',
        ]);

        $tournament = Tournament::findOrFail($id);
        $match = TournamentMatch::findOrFail($request->match_id);

        // Security 1: Does this match belong to this tournament?
        if ($match->tournament_id !== $tournament->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        // Security 2: Is this the current active round?
        $currentActiveRound = $tournament->matches->max('round_number') ?? 1;
        
        if ($tournament->status !== 'active' || $match->round_number != $currentActiveRound) {
            return back()->with('error', 'You can only edit match results in the current active round.');
        }

        // Apply the override
        $match->result_code = $request->result_code;
        $match->save();

        return back()->with('success', 'Match result updated successfully!');
    }

    public function fetchRoundMatches(Request $request, $id) 
    {
        $tournament = Tournament::findOrFail($id);
        $round = $request->query('round', 1);
        
        $matches = $tournament->getMatchesForRound($round);

        // 1. Check if the frontend asked for the admin view
        $isAdmin = $request->query('admin') === 'true';

        // 2. Return the correct partial path and pass the isAdmin flag
        return view('admin.tournaments.partials.matches_rows', [
            'matches' => $matches,
            'isAdmin' => $isAdmin
        ]);
    }

    public function startTournament(Request $request, $id, SwissPairingGenerator $pairingGenerator)
    {
        $tournament = Tournament::findOrFail($id);

        // 1. Safety Check: Is it actually in registration?
        if ($tournament->status !== 'registration') {
            return back()->with('error', 'This tournament has already started or is completed.');
        }

        // 2. Fetch all eligible players (exclude any who dropped during registration)
        $entries = $tournament->entries()->get();

        // 3. Safety Check: Do we have enough players?
        if ($entries->count() < 2) {
            return back()->with('error', 'You need at least 2 players to start a tournament.');
        }

        // 4. Generate Round 1 Pairings using your Swiss service
        $pairingGenerator->generatePairings($entries, 1);

        // 5. Lock registration and set the tournament to active
        $tournament->status = 'active';
        $tournament->save();

        return back()->with('success', 'Tournament started! Round 1 pairings have been generated.');
    }

    public function dropPlayer(Request $request, $id)
    {
        $request->validate([
            'entry_id' => 'required|exists:tournament_entries,id'
        ]);

        $tournament = Tournament::findOrFail($id);

        // Rule 1: Drops can only happen when the tournament is active
        if ($tournament->status !== 'active') {
            return back()->with('error', 'Players can only be dropped while the tournament is active.');
        }

        // Fetch the specific entry, ensuring it belongs to this tournament
        $entry = TournamentEntry::where('id', $request->entry_id)
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        if ($entry->is_dropped) {
            return back()->with('error', 'This player has already dropped from the tournament.');
        }

        // Rule 2: Flag the player as dropped
        $entry->is_dropped = true;
        $entry->save();

        // Rule 3: Concede current match if it is in progress
        $currentRound = $tournament->matches()->max('round_number');
        
        if ($currentRound) {
            // Find any unresolved match for this player in the current round
            $activeMatch = TournamentMatch::where('round_number', $currentRound)
                ->whereNull('result_code') // Match is still in progress
                ->where(function ($query) use ($entry) {
                    $query->where('player1_entry_id', $entry->id)
                          ->orWhere('player2_entry_id', $entry->id);
                })
                ->first();

            // If an active match exists, the opponent gets the automatic win
            if ($activeMatch) {
                if ($activeMatch->player1_entry_id === $entry->id) {
                    // Player 1 dropped -> Player 2 wins
                    $activeMatch->result_code = 2;
                } else {
                    // Player 2 dropped -> Player 1 wins
                    $activeMatch->result_code = 1;
                }
                $activeMatch->save();
            }
        }

        return back()->with('success', $entry->user->nickname . ' has been dropped from the tournament and their active match has been conceded.');
    }

    public function generateNextRound(
        Request $request, 
        $id, 
        SwissPairingGenerator $pairingGenerator, 
        TournamentServices $tournamentServices,
        EloCalculator $eloCalculator
    ) {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->status !== 'active') {
            return back()->with('error', 'Tournament is not active.');
        }

        $currentRound = $tournament->matches()->max('round_number') ?? 1;

        // 1. Ensure all matches are reported
        $unreportedMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', $currentRound)
            ->whereNull('result_code')
            ->count();

        if ($unreportedMatches > 0) {
            return back()->with('error', "Cannot generate next round. {$unreportedMatches} matches are still in progress.");
        }

        // 2. Are we trying to generate a round past the limit?
        if ($currentRound >= $tournament->total_rounds) {
            return back()->with('error', 'This was the final round! Please click "Finalize Tournament" instead.');
        }

        // 3. Process the stats for the round that just finished!
        $tournamentServices->processRoundStats($tournament->id, $currentRound, $eloCalculator);

        // NEW: Update Rankings and Tiebreakers
        $tournamentServices->updateStandingsAndTiebreakers($tournament->id);

        // 4. Generate the new pairings
        $activeEntries = $tournament->entries()->where('is_dropped', false)->get();

        $nextRound = $currentRound + 1;
        
        $pairingGenerator->generatePairings($activeEntries, $nextRound);

        return back()->with('success', "Round {$currentRound} stats saved. Round {$nextRound} pairings generated!");
    }

    /**
     * Finalize the tournament after the last round.
     */
    public function finalizeTournament(
        Request $request, 
        $id, 
        TournamentServices $tournamentServices,
        EloCalculator $eloCalculator
    ) {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->status !== 'active') {
            return back()->with('error', 'Only active tournaments can be finalized.');
        }

        $currentRound = $tournament->matches()->max('round_number');

        // 1. Ensure all matches in the final round are reported
        $unreportedMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', $currentRound)
            ->whereNull('result_code')
            ->count();

        if ($unreportedMatches > 0) {
            return back()->with('error', "Cannot finalize. {$unreportedMatches} matches are still in progress.");
        }

        // 2. Process the stats for the final round
        $tournamentServices->processRoundStats($tournament->id, $currentRound, $eloCalculator);

        // NEW: Do one final Ranking update for the official final standings
        $tournamentServices->updateStandingsAndTiebreakers($tournament->id);

        // 3. Process Archetype stats for the whole tournament
        $tournamentServices->processArchetypeStats($tournament->id);

        // 4. Close the tournament
        $tournament->status = 'completed';
        $tournament->save();

        return back()->with('success', 'Tournament finalized successfully! All stats and Elo ratings have been locked.');
    }
    
    public function assignArchetype(Request $request, ArchetypeService $archetypeService)
    {
        $request->validate([
            'global_deck_id' => 'required|exists:global_decks,id',
            'archetype_id' => 'required'
        ]);

        $globalDeck = GlobalDeck::findOrFail($request->global_deck_id);
        $archetypeId = $request->archetype_id;

        // If the admin selected "Create New"
        if ($archetypeId === 'new' && $request->filled('new_archetype_name')) {
            // Grab the very first card in the deck to act as a placeholder Key Card
            $firstContent = $globalDeck->contents()->first();
            
            $newArchetype = Archetype::create([
                'name' => $request->new_archetype_name,
                'key_card_id' => $firstContent ? $firstContent->card_id : null
            ]);
            $archetypeId = $newArchetype->id;
        }

        // Assign it to the Global Deck
        $globalDeck->update(['archetype_id' => $archetypeId]);

        // Trigger the recalculation so this deck's stats are immediately added to the archetype
        $archetype = Archetype::find($archetypeId);
        if ($archetype) {
            $archetypeService->recalculateArchetypeStats($archetype);
        }

        return back()->with('success', 'Archetype successfully assigned! Stats recalculated.');
    }

    public function syncCards()
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

    public function storeTournament(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'start_date'   => 'required|date',
            'capacity'     => 'required|integer|min:2',
            'total_rounds' => 'required|integer|min:1|max:10',
        ]);

        Tournament::create([
            'name'              => $request->name,
            'start_date'        => $request->start_date,
            'capacity'          => $request->capacity,
            'total_rounds'      => $request->total_rounds,
            'registered_player' => 0,
            'status'            => 'registration', // Always starts in registration phase
        ]);

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament created and opened for registration!');
    }

    public function togglePlayerStatus($id)
    {
        $player = User::withTrashed()->findOrFail($id);
        
        if ($player->trashed()) {
            $player->restore(); // Reactivate
            $status = 'activated';
        } else {
            $player->delete(); // Deactivate (Soft Delete)
            $status = 'deactivated';
        }

        return back()->with('success', "Player '{$player->nickname}' has been successfully {$status}.");
    }

    public function storeArchetype(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_id' => 'nullable|string' // e.g., 'sv6-130'
        ]);

        $keyCardId = null;
        if ($request->api_id) {
            $card = Card::where('api_id', $request->api_id)->first();
            if ($card) $keyCardId = $card->id;
        }

        Archetype::create([
            'name' => $request->name,
            'key_card_id' => $keyCardId,
            'times_played' => 0,
            'wins' => 0,
        ]);

        return back()->with('success', 'Archetype created successfully!');
    }

    public function updateArchetype(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_id' => 'nullable|string'
        ]);

        $archetype = Archetype::findOrFail($id);
        
        $keyCardId = $archetype->key_card_id;
        if ($request->api_id) {
            $card = Card::where('api_id', $request->api_id)->first();
            if ($card) {
                $keyCardId = $card->id;
            } else {
                return back()->withErrors(['api_id' => 'Card API ID not found in database.']);
            }
        }

        $archetype->update([
            'name' => $request->name,
            'key_card_id' => $keyCardId,
        ]);

        return back()->with('success', 'Archetype updated successfully!');
    }
}
