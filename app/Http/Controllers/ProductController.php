<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Products/Index', [
            'category' => $request->string('category')->toString(),
            'search' => $request->string('search')->toString(),
            'page' => max(1, $request->integer('page', 1)),
        ]);
    }

    public function show(string $slug): Response
    {
        return Inertia::render('Products/Show', [
            'slug' => $slug,
        ]);
    }
}
