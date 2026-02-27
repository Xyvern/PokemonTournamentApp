<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    protected $fillable = [
        'api_id', 'name', 'series', 'printed_total', 'total',
        'ptcgo_code', 'release_date', 'updated_at_api'
    ];

    public function legalities()
    {
        return $this->hasOne(SetLegality::class);
    }

    public function images()
    {
        return $this->hasOne(SetImage::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }
}
