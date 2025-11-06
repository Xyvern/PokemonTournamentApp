<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardWeakness extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_id', 'type', 'value'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
