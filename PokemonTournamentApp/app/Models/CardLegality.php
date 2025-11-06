<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardLegality extends Model
{
    public $timestamps = true;
    protected $fillable = ['card_id', 'format', 'status'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
