<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Scout\Searchable;

class Product extends Model implements HasMedia
{
    use Searchable;
    use InteractsWithMedia;
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'unit_price',
        'max_quantity',
        'unit',
        'status',
    ];
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
