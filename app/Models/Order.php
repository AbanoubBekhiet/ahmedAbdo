<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_price',
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'orders_products')
                    ->withPivot('number_of_units', 'unit_price', 'total_product_price')
                    ->withTimestamps();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
