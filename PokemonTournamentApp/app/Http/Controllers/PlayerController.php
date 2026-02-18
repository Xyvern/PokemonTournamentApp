<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Deck;
use App\Models\DeckContent;
use App\Models\GlobalDeck;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function storeDeck(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cards' => 'required|json',
        ]);

        $user = Auth::user();
        $deckName = $request->input('name');
        
        // 1. Decode Data
        // Input format: [ 101 => [ 'qty' => 4 ], 102 => [ 'qty' => 2 ] ]
        $inputData = json_decode($request->input('cards'), true);
        if (empty($inputData)) {
            return back()->with('error', 'Deck cannot be empty.');
        }
        
        // 2. Fetch API IDs for Hashing
        // We need to map the incoming DB IDs (keys) to API IDs (e.g., 101 -> 'sv1-86')
        // This ensures our hash matches the Seeder logic perfectly.
        $dbIds = array_keys($inputData);
        $cardLookup = Card::whereIn('id', $dbIds)->pluck('api_id', 'id'); // Returns [ 101 => 'sv1-86', ... ]
        
        // 3. Prepare Data Structure
        $cardsToProcess = [];
        
        foreach ($inputData as $dbId => $data) {
            // Skip if the card ID sent doesn't exist in our DB
            if (!isset($cardLookup[$dbId])) continue;
            
            $qty = (int) $data['qty'];
            if ($qty <= 0) continue;
            
            $apiId = $cardLookup[$dbId];
            
            // We store by API ID for sorting/hashing, 
            // but keep the DB ID for the final Insert.
            if (isset($cardsToProcess[$apiId])) {
                $cardsToProcess[$apiId]['qty'] += $qty;
            } else {
                $cardsToProcess[$apiId] = [
                    'id'  => $dbId, // Needed for foreign key
                    'qty' => $qty   // Needed for hash
                ];
            }
        }
        
        // 4. Generate Hash (Sort by API ID)
        ksort($cardsToProcess); // Sorts alphabetically by API ID key
        
        $hashString = "";
        foreach ($cardsToProcess as $apiId => $data) {
            // Hash String Format: "sv1-86:4|sv2-185:2|"
            $hashString .= "{$apiId}:{$data['qty']}|";
        }
        $deckHash = hash('sha256', $hashString);

        // 5. Handle Global Deck (Transaction ensures data integrity)
        DB::transaction(function () use ($user, $deckName, $deckHash, $cardsToProcess) {
            
            // Check if this exact deck list exists globally
            $globalDeck = GlobalDeck::where('deck_hash', $deckHash)->first();

            if (!$globalDeck) {
                // A. Create new Global Deck
                $globalDeck = GlobalDeck::create([
                    'deck_hash'    => $deckHash,
                    'archetype_id' => null, // Needs assignment later
                ]);

                // B. Insert Deck Content
                foreach ($cardsToProcess as $data) {
                    DeckContent::create([
                        'global_deck_id' => $globalDeck->id,
                        'card_id'        => $data['id'], // Use the DB ID here
                        'quantity'       => $data['qty']
                    ]);
                }
            }

            // 6. Create User Deck linked to the Global Deck
            Deck::create([
                'user_id'        => $user->id,
                'global_deck_id' => $globalDeck->id,
                'name'           => $deckName,
            ]);
        });

        return redirect()->route('player.mydecks')->with('success', 'Deck saved successfully!');
    }

    public function fetchRoundMatches(Request $request, $id)
    {
        $tournament = Tournament::findOrFail($id);
        $round = $request->input('round');

        // Use the model method you provided
        $matches = $tournament->getMatchesForRound($round);

        // Return the partial view with the new matches
        // We render it to HTML string to send back to AJAX
        return view('tournaments.partials.matches_rows', compact('matches'))->render();
    }

    public function dropTournament($id, Request $request)
    {
        $tournament = Tournament::findOrFail($id);

        // 1. Find the actual Model, not just the ID
        // We also ensure this entry actually belongs to the user to prevent hacking
        $entry = TournamentEntry::where('id', $request->entry_id)
                    ->where('user_id', Auth::id()) // SECURITY CHECK
                    ->first();

        // 2. Check if entry exists (Handles "You are not registered" case)
        if (!$entry) {
            return redirect()->route('tournaments.detail', $id)
                ->with('error', 'Registration not found or unauthorized.');
        }

        // 3. Check Tournament Status
        if ($tournament->status !== 'registration') {
            return redirect()->route('tournaments.detail', $id)
                ->with('error', 'Cannot drop from a tournament that has already started.');
        }

        // 4. Update Player Count safely
        $tournament->decrement('registered_player'); // Safer than $val = $val - 1
        
        // 5. Delete the entry
        $entry->delete();

        return redirect()->route('tournaments.detail', $id)
            ->with('success', 'You have dropped from the tournament.');
    }

    public function registerTournament($id, Request $request)
    {
        $tournament = Tournament::findOrFail($id);
        $deck = Deck::where('id', $request->deck_id)->where('user_id', Auth::id())->first();

        // 1. Check if user is already registered
        $existingEntry = TournamentEntry::where('tournament_id', $id)
                            ->where('user_id', Auth::id())
                            ->first();

        if ($existingEntry) {
            return redirect()->route('tournaments.detail', $id)
                ->with('error', 'You are already registered for this tournament.');
        }

        // 2. Check Tournament Status
        if ($tournament->status !== 'registration') {
            return redirect()->route('tournaments.detail', $id)
                ->with('error', 'Registration for this tournament is closed.');
        }

        // 3. Create Tournament Entry
        TournamentEntry::create([
            'tournament_id' => $id,
            'user_id'       => Auth::id(),
            'deck_id'       => $deck->id ?? null, // You can extend this to allow deck selection during registration
        ]);

        // 4. Increment Player Count safely
        $tournament->increment('registered_player'); // Safer than $val = $val + 1

        return redirect()->route('tournaments.detail', $id)
            ->with('success', 'You have successfully registered for the tournament!');
    }
}
