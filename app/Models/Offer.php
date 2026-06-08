<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'product_id',
        'title',
        'description',
        'end_date',
        'price_after_discount',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class)->where('end_date','>=',date('Y-m-d H:i:s'));
    }
}
