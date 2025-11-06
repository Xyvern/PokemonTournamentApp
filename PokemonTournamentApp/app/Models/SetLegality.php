<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetLegality extends Model
{
    protected $fillable = ['set_id', 'unlimited', 'standard', 'expanded'];

    public function set()
    {
        return $this->belongsTo(Set::class);
    }
}
