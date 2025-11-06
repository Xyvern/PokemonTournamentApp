<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'api_id',
        'set_id',
        'name',
        'supertype',
        'hp',
        'evolves_from',
        'rarity',
        'flavor_text',
        'number',
        'artist',
        'converted_retreat_cost',
    ];

    public function set()
    {
        return $this->belongsTo(Set::class);
    }

    public function subtypes() { return $this->hasMany(CardSubtype::class); }
    public function types() { return $this->hasMany(CardType::class); }
    public function abilities() { return $this->hasMany(CardAbility::class); }
    public function attacks() { return $this->hasMany(CardAttack::class); }
    public function weaknesses() { return $this->hasMany(CardWeakness::class); }
    public function retreatCosts() { return $this->hasMany(CardRetreatCost::class); }
    public function pokedexNumbers() { return $this->hasMany(CardPokedexNumber::class); }
    public function legalities() { return $this->hasMany(CardLegality::class); }
    public function images() { return $this->hasOne(CardImage::class); }
}