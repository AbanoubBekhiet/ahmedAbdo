<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Target extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'goal',
        'points'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_targets', 'target_id', 'user_id');
    }

}
