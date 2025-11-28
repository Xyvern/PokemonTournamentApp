<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardRule extends Model
{
    public $timestamps = true;
    protected $fillable = [
        'card_id',
        'text',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
