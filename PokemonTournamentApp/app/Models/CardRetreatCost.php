<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardRetreatCost extends Model
{
    public $timestamps = false;
    protected $fillable = ['card_id', 'cost'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
