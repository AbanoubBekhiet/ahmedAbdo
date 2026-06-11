<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'shop_name',
        'address',
        'fcm_token',
        'total_orders_price_in_current_month',
    ];
    
    public function user(){
        return $this->belongsTo(User::class);
    }
}
