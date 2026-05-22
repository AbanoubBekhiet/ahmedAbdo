<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function category(){
        return $this->belongsTo(Category::class);
    }
    public function carts(){
        return $this->hasMany(Cart::class);
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'orders_products')
                    ->withPivot('number_of_units', 'unit_price', 'total_price')
                    ->withTimestamps();
    }
}
