<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'price',
        'stock',
        'description',
        'power',
        'warranty',
        'specifications',
        'images',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'specifications' => 'array',
        'images' => 'array',
    ];

    protected $appends = ['image_urls'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the full URLs for product images
     * This automatically converts stored paths to accessible URLs
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $this->images);
    }
}
