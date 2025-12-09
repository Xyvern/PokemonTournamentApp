<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    // Explicitly define the table name
    protected $table = 'tournament_matches';

    protected $fillable = [
        'tournament_id',
        'round_number',
        'player1_entry_id',
        'player2_entry_id',
        'result_code',
        'elo_gain',
    ];

    protected $casts = [
        'result_code' => 'integer',
        'round_number' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */
    const RESULT_P1_WIN = 1;
    const RESULT_P2_WIN = 2;
    const RESULT_TIE = 3;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'player1_entry_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(TournamentEntry::class, 'player2_entry_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the match has been played/reported.
     */
    public function isReported(): bool
    {
        return !is_null($this->result_code);
    }

    /**
     * Get the winner entry (if result is decisive).
     */
    public function getWinnerAttribute()
    {
        if ($this->result_code === self::RESULT_P1_WIN) {
            return $this->player1;
        }
        if ($this->result_code === self::RESULT_P2_WIN) {
            return $this->player2;
        }
        return null; // Tie or not played
    }
}