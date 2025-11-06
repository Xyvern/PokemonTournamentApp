<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardPokedexNumber extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_id', 'number'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
