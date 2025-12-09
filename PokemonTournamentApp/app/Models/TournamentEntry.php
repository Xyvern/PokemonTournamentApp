<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentEntry extends Model
{
    protected $fillable = [
        'tournament_id', 
        'user_id', 
        'deck_id', 
        'rank', 
        'points', 
        'wins', 
        'losses', 
        'ties', 
        'omw_percentage', 
        'oomw_percentage',
        'total_elo_gain',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get all matches this player participated in.
     */
    public function matches()
    {
        // Update reference to TournamentMatch
        return TournamentMatch::where('player1_entry_id', $this->id)
            ->orWhere('player2_entry_id', $this->id)
            ->get();
    }
    
    public function calculateScore()
    {
        return ($this->wins * 3) + ($this->ties * 1);
    }
}
