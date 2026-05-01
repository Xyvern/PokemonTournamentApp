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

        if ($tournament->status === 'completed') {
            return back()->with('error', 'Cannot drop players from a completed tournament.');
        }

        $entry = TournamentEntry::where('id', $request->entry_id)
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        if ($entry->is_dropped) {
            return back()->with('error', 'This player has already dropped from the tournament.');
        }

        // NEW LOGIC: If the tournament hasn't started yet, physically remove them to free up capacity
        if ($tournament->status === 'registration') {
            $nickname = $entry->user->nickname;
            $entry->delete();
            $tournament->decrement('registered_player');
            
            return back()->with('success', "{$nickname} has been removed from the registration list.");
        }

        // ORIGINAL LOGIC: If the tournament is active, flag them as dropped and concede matches
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

        $apiKey = env('POKEMON_TCG_API_KEY');

        // 1. Find the latest release date in your DB
        $latestSet = \App\Models\Set::orderBy('release_date', 'desc')->first();
        $latestDate = $latestSet ? \Carbon\Carbon::parse($latestSet->release_date) : null;

        // 2. Fetch ALL Sets, ordered Newest to Oldest
        $setsResponse = \Illuminate\Support\Facades\Http::withHeaders([
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($allSets, $latestDate, &$cardsAdded, &$setsAdded, $apiKey) {
            foreach ($allSets as $setData) {
                
                $apiSetDate = \Carbon\Carbon::parse($setData['releaseDate']);

                // Rule 1: Break if the set is older than our database
                if ($latestDate && $apiSetDate->lt($latestDate)) {
                    break;
                }

                // Rule 2: Skip non-Standard sets
                if (!isset($setData['legalities']['standard']) || strtolower($setData['legalities']['standard']) !== 'legal') {
                    continue;
                }

                // Rule 3: Skip if it already exists
                if (\App\Models\Set::where('api_id', $setData['id'])->exists()) {
                    continue; 
                }

                // --- 3. Create the Set ---
                $set = \App\Models\Set::create([
                    'api_id'         => $setData['id'],
                    'name'           => $setData['name'],
                    'series'         => $setData['series'] ?? 'Unknown',
                    'printed_total'  => $setData['printedTotal'] ?? null,
                    'total'          => $setData['total'] ?? null,
                    'ptcgo_code'     => $setData['ptcgoCode'] ?? null,
                    'release_date'   => $setData['releaseDate'] ?? null,
                    'updated_at_api' => $setData['updatedAt'] ?? null,
                ]);

                // Set Relations
                if (isset($setData['legalities'])) {
                    $set->legalities()->create($setData['legalities']);
                }

                if (isset($setData['images'])) {
                    $set->images()->create($setData['images']);
                }
                
                $setsAdded++;

                // --- 4. Fetch Cards for this specific NEW Set ---
                $cardsResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Api-Key' => $apiKey
                ])->get("https://api.pokemontcg.io/v2/cards", [
                    'q' => "set.id:{$setData['id']}"
                ]);
                
                if ($cardsResponse->successful()) {
                    $cardsData = $cardsResponse->json()['data'];
                    
                    foreach ($cardsData as $cardData) {
                        
                        // Create main card
                        $card = \App\Models\Card::create([
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
                            'is_playable'            => true, // Assumed true as we are filtering strictly by standard sets
                        ]);

                        // Subtypes
                        foreach ($cardData['subtypes'] ?? [] as $subtype) {
                            \App\Models\CardSubtype::create([
                                'card_id' => $card->id,
                                'subtype' => $subtype,
                            ]);
                        }

                        // Types
                        foreach ($cardData['types'] ?? [] as $type) {
                            \App\Models\CardType::create([
                                'card_id' => $card->id,
                                'type' => $type,
                            ]);
                        }

                        // Rules
                        foreach ($cardData['rules'] ?? [] as $ruleText) {
                            \App\Models\CardRule::create([
                                'card_id' => $card->id,
                                'text' => $ruleText,
                            ]);
                        }

                        // Abilities
                        foreach ($cardData['abilities'] ?? [] as $ability) {
                            \App\Models\CardAbility::create([
                                'card_id' => $card->id,
                                'name' => $ability['name'] ?? null,
                                'text' => $ability['text'] ?? null,
                                'type' => $ability['type'] ?? null,
                            ]);
                        }

                        // Attacks & Attack Costs
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

                        // Weaknesses
                        foreach ($cardData['weaknesses'] ?? [] as $weakness) {
                            \App\Models\CardWeakness::create([
                                'card_id' => $card->id,
                                'type' => $weakness['type'] ?? null,
                                'value' => $weakness['value'] ?? null,
                            ]);
                        }

                        // Retreat Cost
                        foreach ($cardData['retreatCost'] ?? [] as $cost) {
                            \App\Models\CardRetreatCost::create([
                                'card_id' => $card->id,
                                'cost' => $cost,
                            ]);
                        }

                        // National Pokedex Numbers
                        foreach ($cardData['nationalPokedexNumbers'] ?? [] as $num) {
                            \App\Models\CardPokedexNumber::create([
                                'card_id' => $card->id,
                                'number' => $num,
                            ]);
                        }

                        // Legalities
                        foreach ($cardData['legalities'] ?? [] as $format => $status) {
                            \App\Models\CardLegality::create([
                                'card_id' => $card->id,
                                'format' => $format,
                                'status' => $status,
                            ]);
                        }

                        // Images
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
                    \Illuminate\Support\Facades\Log::warning("Failed to fetch cards for set: {$setData['id']}");
                }
            }
        });

        if ($setsAdded === 0) {
            return back()->with('success', 'Your database is already up to date! No new standard sets found.');
        }

        return back()->with('success', "Successfully synced {$setsAdded} new sets and {$cardsAdded} new cards!");
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

    public function togglePlayable($id)
    {
        $card = \App\Models\Card::findOrFail($id);
        
        // Flip the boolean (true becomes false, false becomes true)
        $card->is_playable = !$card->is_playable;
        $card->save();

        $statusText = $card->is_playable ? 'Playable' : 'Not Playable';

        return back()->with('success', "Card '{$card->name}' has been marked as {$statusText}.");
    }

    public function cancelTournament($id)
    {
        $tournament = Tournament::findOrFail($id);

        if ($tournament->status !== 'registration') {
            return back()->with('error', 'You can only cancel tournaments that are in the registration phase.');
        }

        // 1. Mark all current entries as dropped
        $tournament->entries()->update(['is_dropped' => true]);

        // 2. Change status to completed so it locks
        $tournament->status = 'completed';
        $tournament->save();

        return back()->with('success', 'Tournament has been cancelled. All registered players have been dropped.');
    }
}
