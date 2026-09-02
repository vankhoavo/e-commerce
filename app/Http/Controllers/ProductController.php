<?php

namespace App\Http\Controllers;

use App\Models\AdminProduct;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Home', [
            'categories' => ProductCategory::query()->where('is_active', true)->withCount('products')->orderBy('name')->get(['id','name','slug','icon']),
            'featuredProducts' => $this->productQuery()->where('is_active', true)->whereHas('category', fn ($q) => $q->where('slug', 'laptop'))->orderByDesc('sold_count')->limit(6)->get()->map(fn (AdminProduct $p) => $this->transform($p)),
        ]);
    }

    public function index(Request $request): Response
    {
        $query = $this->productQuery()->where('is_active', true);
        $category = $request->string('category')->toString();
        $search = $request->string('search')->toString();
        if ($category && $category !== 'all') $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        if ($search !== '') $query->where(fn ($q) => $q->where('name','like','%'.$search.'%')->orWhere('brand','like','%'.$search.'%')->orWhere('sku','like','%'.$search.'%'));
        $products = $query->latest('id')->get()->map(fn (AdminProduct $p) => $this->transform($p))->values();
        return Inertia::render('Products/Index', ['category'=>$category,'search'=>$search,'products'=>$products,'categories'=>ProductCategory::query()->where('is_active',true)->withCount('products')->orderBy('name')->get(['id','name','slug','icon'])]);
    }

    public function show(string $slug): Response
    {
        $product = AdminProduct::query()->with('category:id,name,slug')->where('slug',$slug)->where('is_active',true)->first();
        return Inertia::render('Products/Show', ['product'=>$product ? $this->transform($product) : null]);
    }

    public function catalog(): JsonResponse
    {
        return response()->json(['products'=>AdminProduct::query()->where('is_active',true)->get(['id','slug','name','brand','price','image','stock'])->map(fn(AdminProduct $p)=>['id'=>$p->id,'slug'=>$p->slug,'name'=>$p->name,'brand'=>$p->brand,'price'=>(int)$p->price,'image'=>$p->image,'stock'=>(int)$p->stock])->values()]);
    }

    private function productQuery(){ return AdminProduct::query()->with('category:id,name,slug'); }

    private function transform(AdminProduct $p): array
    {
        $gallery=$p->gallery?:array_values(array_filter([$p->image]));
        return ['id'=>$p->id,'slug'=>$p->slug,'name'=>$p->name,'category'=>$p->category?->name??'Sản phẩm','categorySlug'=>$p->category?->slug,'brand'=>$p->brand??'TechStore','price'=>(int)$p->price,'oldPrice'=>$p->old_price?(int)$p->old_price:null,'image'=>$p->image,'gallery'=>$gallery,'badge'=>$p->badge,'rating'=>(float)$p->rating,'sold'=>(int)$p->sold_count,'stock'=>(int)$p->stock,'shortDescription'=>$p->short_description,'description'=>$p->description,'specs'=>$p->specs?:[]];
    }
}
