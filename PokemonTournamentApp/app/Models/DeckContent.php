<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeckContent extends Model
{
    // Pivot tables usually don't need timestamps
    public $timestamps = false;
    
    protected $fillable = ['global_deck_id', 'card_id', 'quantity'];

    public function globalDeck(): BelongsTo
    {
        return $this->belongsTo(GlobalDeck::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}