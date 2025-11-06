<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardAttackCost extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_attack_id', 'cost'];

    public function attack()
    {
        return $this->belongsTo(CardAttack::class, 'card_attack_id');
    }
}
