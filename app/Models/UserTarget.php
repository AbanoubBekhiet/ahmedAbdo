<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTarget extends Model
{
    protected $fillable = [
        'user_id',
        'target_id',
        'order_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function target()
    {
        return $this->belongsTo(Target::class)->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
