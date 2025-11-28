<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deck extends Model
{
    protected $fillable = ['user_id', 'global_deck_id', 'name'];

    /**
     * The User who owns this deck instance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The underlying structure (the 60 cards).
     */
    public function globalDeck(): BelongsTo
    {
        return $this->belongsTo(GlobalDeck::class);
    }

    /**
     * A helper shortcut to get the archetype of this user's deck.
     * $deck->archetype
     */
    public function archetype()
    {
        return $this->hasOneThrough(Archetype::class, GlobalDeck::class, 'id', 'id', 'global_deck_id', 'archetype_id');
    }
}