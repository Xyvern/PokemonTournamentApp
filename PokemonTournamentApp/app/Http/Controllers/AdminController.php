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
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Updates the result of a specific tournament match.
     */
    public function updateMatchResult(Request $request, $id)
    {
        $request->validate([
            'match_id' => 'required|exists:tournament_matches,id',
            'result_code' => 'nullable|in:1,2,3',
        ]);

        $tournament = Tournament::findOrFail($id);
        $match = TournamentMatch::findOrFail($request->match_id);

        if ($match->tournament_id !== $tournament->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        $currentActiveRound = $tournament->matches->max('round_number') ?? 1;
        
        if ($tournament->status !== 'active' || $match->round_number != $currentActiveRound) {
            return back()->with('error', 'You can only edit match results in the current active round.');
        }

        $match->result_code = $request->result_code;
        $match->save();

        return back()->with('success', 'Match result updated successfully!');
    }

    /**
     * Fetches the HTML partial for a specific tournament round's matches.
     */
    public function fetchRoundMatches(Request $request, $id) 
    {
        $tournament = Tournament::findOrFail($id);
        $round = $request->query('round', 1);
        
        $matches = $tournament->getMatchesForRound($round);
        $isAdmin = $request->query('admin') === 'true';

        return view('admin.tournaments.partials.matches_rows', [
            'matches' => $matches,
            'isAdmin' => $isAdmin
        ]);
    }

    /**
     * Initiates a tournament, locks registration, and generates the first round of pairings.
     */
    public function startTournament(Request $request, $id, SwissPairingGenerator $pairingGenerator)
    {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->status !== 'registration') {
            return back()->with('error', 'This tournament has already started or is completed.');
        }

        $entries = $tournament->entries()->get();

        if ($entries->count() < 2) {
            return back()->with('error', 'You need at least 2 players to start a tournament.');
        }

        $pairingGenerator->generatePairings($entries, 1);

        $tournament->status = 'active';
        $tournament->save();

        return back()->with('success', 'Tournament started! Round 1 pairings have been generated.');
    }

    /**
     * Drops a player from a tournament, either removing them from registration or conceding their active match.
     */
    public function dropPlayer(Request $request, $id)
    {
        $request->validate([
            'entry_id' => 'required|exists:tournament_entries,id'
        ]);

        $tournament = Tournament::findOrFail($id);

        if ($tournament->status === 'completed') {
            return back()->with('error', 'Cannot drop players from a completed tournament.');
        }

        $entry = TournamentEntry::where('id', $request->entry_id)
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        if ($entry->is_dropped) {
            return back()->with('error', 'This player has already dropped from the tournament.');
        }

        if ($tournament->status === 'registration') {
            $nickname = $entry->user->nickname;
            $entry->delete();
            $tournament->decrement('registered_player');
            
            return back()->with('success', "{$nickname} has been removed from the registration list.");
        }

        $entry->is_dropped = true;
        $entry->save();

        $currentRound = $tournament->matches()->max('round_number');
        
        if ($currentRound) {
            $activeMatch = TournamentMatch::where('round_number', $currentRound)
                ->whereNull('result_code')
                ->where(function ($query) use ($entry) {
                    $query->where('player1_entry_id', $entry->id)
                          ->orWhere('player2_entry_id', $entry->id);
                })->first();

            if ($activeMatch) {
                $activeMatch->result_code = ($activeMatch->player1_entry_id === $entry->id) ? 2 : 1;
                $activeMatch->save();
            }
        }

        return back()->with('success', "{$entry->user->nickname} has been dropped and their active match conceded.");
    }

    /**
     * Processes the current round's statistics and generates pairings for the next round.
     */
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

        $unreportedMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', $currentRound)
            ->whereNull('result_code')
            ->count();

        if ($unreportedMatches > 0) {
            return back()->with('error', "Cannot generate next round. {$unreportedMatches} matches are still in progress.");
        }

        if ($currentRound >= $tournament->total_rounds) {
            return back()->with('error', 'This was the final round! Please click "Finalize Tournament" instead.');
        }

        $tournamentServices->processRoundStats($tournament->id, $currentRound, $eloCalculator);
        $tournamentServices->updateStandingsAndTiebreakers($tournament->id);

        $activeEntries = $tournament->entries()->where('is_dropped', false)->get();
        $nextRound = $currentRound + 1;
        
        $pairingGenerator->generatePairings($activeEntries, $nextRound);

        return back()->with('success', "Round {$currentRound} stats saved. Round {$nextRound} pairings generated!");
    }

    /**
     * Finalizes a tournament, locks all statistics, and updates global standings and Elo ratings.
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

        $unreportedMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', $currentRound)
            ->whereNull('result_code')
            ->count();

        if ($unreportedMatches > 0) {
            return back()->with('error', "Cannot finalize. {$unreportedMatches} matches are still in progress.");
        }

        $tournamentServices->processRoundStats($tournament->id, $currentRound, $eloCalculator);
        $tournamentServices->updateStandingsAndTiebreakers($tournament->id);
        $tournamentServices->processArchetypeStats($tournament->id);

        $tournament->status = 'completed';
        $tournament->save();

        return back()->with('success', 'Tournament finalized successfully! All stats and Elo ratings have been locked.');
    }
    
    /**
     * Assigns an archetype to a global deck and recalculates archetype statistics.
     */
    public function assignArchetype(Request $request, ArchetypeService $archetypeService)
    {
        $request->validate([
            'global_deck_id' => 'required|exists:global_decks,id',
            'archetype_id' => 'required'
        ]);

        $globalDeck = GlobalDeck::findOrFail($request->global_deck_id);
        $archetypeId = $request->archetype_id;

        if ($archetypeId === 'new' && $request->filled('new_archetype_name')) {
            $firstContent = $globalDeck->contents()->first();
            
            $newArchetype = Archetype::create([
                'name' => $request->new_archetype_name,
                'key_card_id' => $firstContent ? $firstContent->card_id : null
            ]);
            $archetypeId = $newArchetype->id;
        }

        $globalDeck->update(['archetype_id' => $archetypeId]);

        $archetype = Archetype::find($archetypeId);
        
        if ($archetype) {
            $archetypeService->recalculateArchetypeStats($archetype);
        }

        return back()->with('success', 'Archetype successfully assigned! Stats recalculated.');
    }

    /**
     * Synchronizes the local database with the official Pokémon TCG API to fetch new sets and cards.
     */
    public function syncCards()
    {
        set_time_limit(0); 

        $apiKey = env('POKEMON_TCG_API_KEY');

        $latestSet = Set::orderBy('release_date', 'desc')->first();
        $latestDate = $latestSet ? Carbon::parse($latestSet->release_date) : null;

        $setsResponse = Http::withHeaders([
            'X-Api-Key' => $apiKey
        ])->get("https://api.pokemontcg.io/v2/sets?orderBy=-releaseDate");
        
        if (!$setsResponse->successful()) {
            return back()->with('error', 'Sets API Error ' . $setsResponse->status() . ': ' . $setsResponse->body());
        }

        $allSets = $setsResponse->json()['data'];

        if (empty($allSets)) {
            return back()->with('error', 'API returned an empty list of sets.');
        }

        $cardsAdded = 0;
        $setsAdded = 0;

        DB::transaction(function () use ($allSets, $latestDate, &$cardsAdded, &$setsAdded, $apiKey) {
            foreach ($allSets as $setData) {
                
                $apiSetDate = Carbon::parse($setData['releaseDate']);

                if ($latestDate && $apiSetDate->lt($latestDate)) {
                    break;
                }

                if (!isset($setData['legalities']['standard']) || strtolower($setData['legalities']['standard']) !== 'legal') {
                    continue;
                }

                if (Set::where('api_id', $setData['id'])->exists()) {
                    continue; 
                }

                $set = Set::create([
                    'api_id'         => $setData['id'],
                    'name'           => $setData['name'],
                    'series'         => $setData['series'] ?? 'Unknown',
                    'printed_total'  => $setData['printedTotal'] ?? null,
                    'total'          => $setData['total'] ?? null,
                    'ptcgo_code'     => $setData['ptcgoCode'] ?? null,
                    'release_date'   => $setData['releaseDate'] ?? null,
                    'updated_at_api' => $setData['updatedAt'] ?? null,
                ]);

                if (isset($setData['legalities'])) {
                    $set->legalities()->create($setData['legalities']);
                }

                if (isset($setData['images'])) {
                    $set->images()->create($setData['images']);
                }
                
                $setsAdded++;

                $cardsResponse = Http::withHeaders([
                    'X-Api-Key' => $apiKey
                ])->get("https://api.pokemontcg.io/v2/cards", [
                    'q' => "set.id:{$setData['id']}"
                ]);
                
                if ($cardsResponse->successful()) {
                    $cardsData = $cardsResponse->json()['data'];
                    
                    foreach ($cardsData as $cardData) {
                        
                        $card = Card::create([
                            'api_id'                 => $cardData['id'] ?? null,
                            'set_id'                 => $set->id,
                            'name'                   => $cardData['name'] ?? null,
                            'supertype'              => $cardData['supertype'] ?? null,
                            'hp'                     => $cardData['hp'] ?? null,
                            'evolves_from'           => $cardData['evolvesFrom'] ?? null,
                            'rarity'                 => $cardData['rarity'] ?? null,
                            'flavor_text'            => $cardData['flavorText'] ?? null,
                            'number'                 => $cardData['number'] ?? null,
                            'artist'                 => $cardData['artist'] ?? null,
                            'converted_retreat_cost' => $cardData['convertedRetreatCost'] ?? null,
                            'is_playable'            => false,
                        ]);

                        foreach ($cardData['subtypes'] ?? [] as $subtype) {
                            \App\Models\CardSubtype::create([
                                'card_id' => $card->id,
                                'subtype' => $subtype,
                            ]);
                        }

                        foreach ($cardData['types'] ?? [] as $type) {
                            \App\Models\CardType::create([
                                'card_id' => $card->id,
                                'type' => $type,
                            ]);
                        }

                        foreach ($cardData['rules'] ?? [] as $ruleText) {
                            \App\Models\CardRule::create([
                                'card_id' => $card->id,
                                'text' => $ruleText,
                            ]);
                        }

                        foreach ($cardData['abilities'] ?? [] as $ability) {
                            \App\Models\CardAbility::create([
                                'card_id' => $card->id,
                                'name' => $ability['name'] ?? null,
                                'text' => $ability['text'] ?? null,
                                'type' => $ability['type'] ?? null,
                            ]);
                        }

                        foreach ($cardData['attacks'] ?? [] as $attackData) {
                            $attack = \App\Models\CardAttack::create([
                                'card_id' => $card->id,
                                'name' => $attackData['name'] ?? null,
                                'converted_energy_cost' => $attackData['convertedEnergyCost'] ?? null,
                                'damage' => $attackData['damage'] ?? null,
                                'text' => $attackData['text'] ?? null,
                            ]);

                            foreach ($attackData['cost'] ?? [] as $cost) {
                                \App\Models\CardAttackCost::create([
                                    'card_attack_id' => $attack->id,
                                    'cost' => $cost,
                                ]);
                            }
                        }

                        foreach ($cardData['weaknesses'] ?? [] as $weakness) {
                            \App\Models\CardWeakness::create([
                                'card_id' => $card->id,
                                'type' => $weakness['type'] ?? null,
                                'value' => $weakness['value'] ?? null,
                            ]);
                        }

                        foreach ($cardData['retreatCost'] ?? [] as $cost) {
                            \App\Models\CardRetreatCost::create([
                                'card_id' => $card->id,
                                'cost' => $cost,
                            ]);
                        }

                        foreach ($cardData['nationalPokedexNumbers'] ?? [] as $num) {
                            \App\Models\CardPokedexNumber::create([
                                'card_id' => $card->id,
                                'number' => $num,
                            ]);
                        }

                        foreach ($cardData['legalities'] ?? [] as $format => $status) {
                            \App\Models\CardLegality::create([
                                'card_id' => $card->id,
                                'format' => $format,
                                'status' => $status,
                            ]);
                        }

                        if (isset($cardData['images'])) {
                            \App\Models\CardImage::create([
                                'card_id' => $card->id,
                                'small' => $cardData['images']['small'] ?? null,
                                'large' => $cardData['images']['large'] ?? null,
                            ]);
                        }

                        $cardsAdded++;
                    }
                } else {
                    Log::warning("Failed to fetch cards for set: {$setData['id']}");
                }
            }
        });

        if ($setsAdded === 0) {
            return back()->with('success', 'Your database is already up to date! No new standard sets found.');
        }

        return back()->with('success', "Successfully synced {$setsAdded} new sets and {$cardsAdded} new cards!");
    }

    /**
     * Creates a new tournament and opens it for registration.
     */
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
            'status'            => 'registration',
        ]);

        return redirect()->route('admin.tournaments.index')->with('success', 'Tournament created and opened for registration!');
    }

    /**
     * Toggles a user's active status (ban/unban) using soft deletes.
     */
    public function togglePlayerStatus($id, Request $request)
    {
        $player = User::withTrashed()->findOrFail($id);
        
        if ($player->trashed()) {
            $player->restore();
            $status = 'activated';
        } else {
            $player->delete();
            $status = 'deactivated';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Player '{$player->nickname}' has been successfully {$status}."
            ]);
        }

        return back()->with('success', "Player '{$player->nickname}' has been successfully {$status}.");
    }

    /**
     * Bans a player using their nickname via AJAX request.
     */
    public function banPlayerByNickname(Request $request)
    {
        $request->validate(['nickname' => 'required|string']);
        
        $user = User::where('nickname', $request->nickname)->first();

        if ($user) {
            $user->delete();
            return response()->json(['success' => true, 'message' => 'User banned successfully.']);
        }
        
        return response()->json(['success' => false, 'message' => 'User not found.'], 404);
    }

    /**
     * Creates a new deck archetype.
     */
    public function storeArchetype(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'api_id' => 'nullable|string'
        ]);

        $keyCardId = null;
        
        if ($request->api_id) {
            $card = Card::where('api_id', $request->api_id)->first();
            if ($card) {
                $keyCardId = $card->id;
            }
        }

        Archetype::create([
            'name' => $request->name,
            'key_card_id' => $keyCardId,
            'times_played' => 0,
            'wins' => 0,
        ]);

        return back()->with('success', 'Archetype created successfully!');
    }

    /**
     * Updates an existing deck archetype.
     */
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

    /**
     * Toggles the playable status of a specific card.
     */
    public function togglePlayable($id)
    {
        $card = Card::findOrFail($id);
        
        $card->is_playable = !$card->is_playable;
        $card->save();

        $statusText = $card->is_playable ? 'Playable' : 'Not Playable';

        return back()->with('success', "Card '{$card->name}' has been marked as {$statusText}.");
    }

    /**
     * Cancels a tournament currently in the registration phase and drops all players.
     */
    public function cancelTournament($id)
    {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->status !== 'registration') {
            return back()->with('error', 'You can only cancel tournaments that are in the registration phase.');
        }

        $tournament->entries()->update(['is_dropped' => true]);

        $tournament->status = 'completed';
        $tournament->save();

        return back()->with('success', 'Tournament has been cancelled. All registered players have been dropped.');
    }

    /**
     * Updates the configuration details of an existing tournament.
     */
    public function updateTournament(Request $request, $id)
    {
        $tournament = Tournament::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'start_date'   => 'required|date',
            'capacity'     => 'required|integer|min:4|max:16',
            'total_rounds' => 'required|integer|min:1|max:10',
        ]);

        if ($validated['capacity'] < $tournament->registered_player) {
            return back()->withInput()->withErrors([
                'capacity' => "You cannot reduce capacity below {$tournament->registered_player} because players are already registered. Drop players first."
            ]);
        }

        $tournament->name = $validated['name'];
        $tournament->start_date = $validated['start_date'];
        $tournament->capacity = $validated['capacity'];
        $tournament->total_rounds = $validated['total_rounds'];
        $tournament->save();

        return redirect()->route('admin.tournaments.detail', $tournament->id)
                         ->with('success', 'Tournament updated successfully!');
    }
}