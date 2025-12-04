<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = ['name', 'start_date', 'total_rounds', 'capacity', 'registered_player', 'status'];

    protected $casts = [
        'start_date' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(TournamentEntry::class)->orderBy('points', 'desc');
    }

    public function matches(): HasMany
    {
        // Update reference to TournamentMatch
        return $this->hasMany(TournamentMatch::class);
    }

    public function getMatchesForRound(int $round)
    {
        return $this->matches()->where('round_number', $round)->get();
    }
}
