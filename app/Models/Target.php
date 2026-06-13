<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $fillable = [
        'goal',
        'points'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_targets', 'target_id', 'user_id');
    }

}
