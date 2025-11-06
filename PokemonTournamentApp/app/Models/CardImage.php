<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardImage extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_id', 'small', 'large'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
