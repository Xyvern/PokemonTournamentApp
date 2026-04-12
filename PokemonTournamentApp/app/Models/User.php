<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'nickname',
        'role',
        'elo',
        'matches_played',
        'matches_won',
        'matches_lost',
        'premium_until',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'premium_until' => 'datetime',
    ];

    public $timestamps = true;

    protected $dates = ['deleted_at'];

    public function isPremium()
    {
        // If the column is null, or the date has passed, they are not premium.
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function decks()
    {
        return $this->hasMany(Deck::class);
    }

    public function tournamentEntries()
    {
        return $this->hasMany(TournamentEntry::class);
    }
}
