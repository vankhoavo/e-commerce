<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingFee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'province', 'fee', 'free_ship_from', 'is_active'];

    protected function casts(): array
    {
        return ['fee' => 'integer', 'free_ship_from' => 'integer', 'is_active' => 'boolean'];
    }
}
