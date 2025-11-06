<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardSubtype extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_id', 'subtype'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
