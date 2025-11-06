<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardAttack extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'card_id',
        'name',
        'converted_energy_cost',
        'damage',
        'text',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function costs()
    {
        return $this->hasMany(CardAttackCost::class, 'card_attack_id');
    }
}
