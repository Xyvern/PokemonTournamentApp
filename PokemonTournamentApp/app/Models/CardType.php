<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardType extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_id', 'type'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
