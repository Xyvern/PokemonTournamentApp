<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'gross_amount', 'payment_type', 'status', 'snap_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}