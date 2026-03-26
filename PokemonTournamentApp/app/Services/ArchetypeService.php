<?php
namespace App\Services;

use App\Models\Archetype;
use App\Models\Deck;
use App\Models\TournamentEntry;

class ArchetypeService
{
    /**
     * Recalculates the total wins and times played for a specific Archetype
     * based on all historical tournament entries of its associated Global Decks.
     */
    public function recalculateArchetypeStats(Archetype $archetype)
    {
        // 1. Get all Global Decks currently assigned to this Archetype
        $globalDeckIds = $archetype->globalDecks()->pluck('id');

        if ($globalDeckIds->isEmpty()) {
            $archetype->update(['wins' => 0, 'times_played' => 0]);
            return;
        }

        // 2. Get all User Decks mapped to these Global Decks
        $deckIds = Deck::whereIn('global_deck_id', $globalDeckIds)->pluck('id');

        // 3. Aggregate stats from all Tournament Entries that used these Decks
        $entries = TournamentEntry::whereIn('deck_id', $deckIds)->get();

        $totalWins = $entries->sum('wins');
        $totalMatches = $entries->sum(function ($entry) {
            return $entry->wins + $entry->losses + $entry->ties;
        });

        // 4. Override the Archetype stats with the true historical calculation
        $archetype->update([
            'wins' => $totalWins,
            'times_played' => $totalMatches
        ]);
    }
}