<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardAbility extends Model
{
    public $timestamps = false;
    protected $fillable = ['card_id', 'name', 'text', 'type'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
