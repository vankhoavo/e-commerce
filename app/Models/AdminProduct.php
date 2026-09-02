<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'brand', 'price', 'old_price', 'stock', 'image',
        'short_description', 'description', 'specs', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'integer', 'old_price' => 'integer', 'stock' => 'integer', 'specs' => 'array', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
