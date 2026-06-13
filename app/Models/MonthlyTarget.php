<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyTarget extends Model
{
    protected $fillable = [
        'goal',
        'points'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_monthly_targets', 'monthly_target_id', 'user_id');
    }
}
