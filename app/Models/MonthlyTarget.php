<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyTarget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'goal',
        'points'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_monthly_targets', 'monthly_target_id', 'user_id');
    }
}
