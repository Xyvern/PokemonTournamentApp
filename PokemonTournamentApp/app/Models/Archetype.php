<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Archetype extends Model
{
    protected $fillable = ['name', 'key_card_id', 'times_played', 'wins'];

    /**
     * Get all unique deck lists that fall under this archetype.
     * Example: "Lost Zone Box" has 500 different unique 60-card variations.
     */
    public function globalDecks(): HasMany
    {
        return $this->hasMany(GlobalDeck::class);
    }

    /**
     * The card used as the thumbnail for this archetype.
     */
    public function keyCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'key_card_id');
    }

    public function getWinRateAttribute()
    {
        if ($this->times_played == 0) {
            return 0;
        }
        
        return round(($this->wins / $this->times_played) * 100, 1);
    }
}
