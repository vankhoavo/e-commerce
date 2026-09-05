<?php

namespace App\Http\Controllers;

use App\Models\AdminProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $products = AdminProduct::query()
            ->with('category:id,name')
            ->orderBy('stock')
            ->get()
            ->map(static fn (AdminProduct $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image' => $product->image,
                'category' => $product->category?->name ?? 'Chưa phân loại',
                'stock' => (int) $product->stock,
                'sold' => (int) $product->sold_count,
                'price' => (int) $product->price,
                'is_active' => (bool) $product->is_active,
            ]);

        return Inertia::render('admin/Inventory', [
            'products' => $products,
            'summary' => [
                'stockUnits' => (int) $products->sum('stock'),
                'soldUnits' => (int) $products->sum('sold'),
                'stockValue' => (int) $products->sum(static fn (array $product): int => $product['stock'] * $product['price']),
                'lowStock' => (int) $products->where('stock', '<', 10)->count(),
            ],
        ]);
    }
}
