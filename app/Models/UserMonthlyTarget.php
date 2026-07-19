<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMonthlyTarget extends Model
{
    protected $fillable = [
        'user_id',
        'monthly_target_id',
        'order_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monthlyTarget()
    {
        return $this->belongsTo(MonthlyTarget::class)->withTrashed();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
