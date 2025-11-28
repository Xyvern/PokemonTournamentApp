<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalDeck extends Model
{
    protected $fillable = ['deck_hash', 'archetype_id'];

    /**
     * The archetype this deck belongs to (e.g., "Charizard ex").
     */
    public function archetype(): BelongsTo
    {
        return $this->belongsTo(Archetype::class);
    }

    /**
     * The specific cards inside this deck.
     * This uses the pivot table 'deck_contents'.
     */
    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'deck_contents', 'global_deck_id', 'card_id')
                    ->withPivot('quantity');
    }

    /**
     * The raw pivot rows (useful if you need to query quantity directly without loading Card models).
     */
    public function contents(): HasMany
    {
        return $this->hasMany(DeckContent::class);
    }

    /**
     * Get all the Players (User Decks) that are using this EXACT 60-card list.
     */
    public function decks(): HasMany
    {
        return $this->hasMany(Deck::class);
    }
}