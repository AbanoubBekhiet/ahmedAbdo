<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'min_order_price',
        'min_order_products',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_order_price' => 'float',
        'min_order_products' => 'integer',
    ];

    /**
     * Get all customer profiles associated with this region.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}
