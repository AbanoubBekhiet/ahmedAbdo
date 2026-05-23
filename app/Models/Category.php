<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class Category extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'name',
    ];

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized_webp')
            ->format('webp')
            ->width(800)
            ->height(600)
            ->quality(80)
            ->performOnCollections('category_images');
    }

    public function products(){
        return $this->hasMany(Product::class);
    }
}
