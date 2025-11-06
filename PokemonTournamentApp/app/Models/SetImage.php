<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetImage extends Model
{
    protected $fillable = ['set_id', 'symbol', 'logo'];

    public function set()
    {
        return $this->belongsTo(Set::class);
    }
}
