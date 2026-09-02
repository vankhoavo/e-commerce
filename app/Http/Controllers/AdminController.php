<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AdminProduct;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ShippingFee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        $year = now()->year;
        $ordersByMonth = array_fill(1, 12, 0);
        $revenueByMonth = array_fill(1, 12, 0);

        Order::query()->whereYear('created_at', $year)->get(['created_at', 'total'])->each(function (Order $order) use (&$ordersByMonth, &$revenueByMonth): void {
            $month = (int) $order->created_at->month;
            $ordersByMonth[$month]++;
            $revenueByMonth[$month] += (int) $order->total;
        });

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'orders' => Order::count(),
                'revenue' => (int) Order::sum('total'),
                'customers' => User::query()->where('role', UserRole::CUSTOMER->value)->count(),
                'products' => AdminProduct::count(),
            ],
            'charts' => [
                'months' => array_map(fn (int $month): string => 'T'.$month, range(1, 12)),
                'orders' => array_values($ordersByMonth),
                'revenue' => array_values($revenueByMonth),
            ],
            'recentOrders' => Order::query()->latest()->limit(6)->get(['id', 'code', 'customer_name', 'status', 'total', 'created_at']),
        ]);
    }

    public function categories(): Response
    {
        return Inertia::render('admin/Categories', ['categories' => ProductCategory::query()->withCount('products')->latest()->get()]);
    }

    public function categoryStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'icon' => ['nullable', 'string', 'max:64']]);
        ProductCategory::create([...$data, 'slug' => Str::slug($data['name'])]);
        return back()->with('success', 'Đã thêm danh mục sản phẩm.');
    }

    public function categoryUpdate(Request $request, ProductCategory $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:1000'], 'icon' => ['nullable', 'string', 'max:64'], 'is_active' => ['required', 'boolean']]);
        $category->update([...$data, 'slug' => Str::slug($data['name'])]);
        return back()->with('success', 'Đã cập nhật danh mục.');
    }

    public function products(): Response
    {
        return Inertia::render('admin/Products', ['products' => AdminProduct::query()->with('category:id,name')->latest()->paginate(15)->withQueryString(), 'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function productStore(Request $request): RedirectResponse
    {
        $data = $request->validate($this->productRules());
        AdminProduct::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5))]);
        return back()->with('success', 'Đã thêm sản phẩm.');
    }

    public function productUpdate(Request $request, AdminProduct $product): RedirectResponse
    {
        $rules = $this->productRules();
        $rules['sku'] = ['required', 'string', 'max:64', Rule::unique('admin_products', 'sku')->ignore($product->id)];
        $data = $request->validate($rules);
        $product->update([...$data, 'slug' => Str::slug($data['name']).'-'.$product->id]);
        return back()->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function productToggle(AdminProduct $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);
        return back()->with('success', $product->is_active ? 'Đã kích hoạt sản phẩm.' : 'Đã khóa sản phẩm.');
    }

    public function coupons(): Response
    {
        return Inertia::render('admin/Coupons', ['coupons' => Coupon::query()->latest()->get()]);
    }

    public function couponStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:coupons,code'], 'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1'], 'min_order_amount' => ['required', 'integer', 'min:0'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        if ($data['type'] === 'percent' && $data['value'] > 100) return back()->withErrors(['value' => 'Phần trăm giảm không được vượt quá 100.']);
        Coupon::create([...$data, 'code' => Str::upper($data['code'])]);
        return back()->with('success', 'Đã thêm mã giảm giá.');
    }

    public function shipping(): Response
    {
        return Inertia::render('admin/Shipping', ['fees' => ShippingFee::query()->orderBy('province')->latest()->get()]);
    }

    public function shippingStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'province' => ['nullable', 'string', 'max:120'], 'fee' => ['required', 'integer', 'min:0'], 'free_ship_from' => ['nullable', 'integer', 'min:0']]);
        ShippingFee::create($data);
        return back()->with('success', 'Đã thêm cấu hình phí vận chuyển.');
    }

    public function shippingUpdate(Request $request, ShippingFee $shippingFee): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'province' => ['nullable', 'string', 'max:120'], 'fee' => ['required', 'integer', 'min:0'], 'free_ship_from' => ['nullable', 'integer', 'min:0'], 'is_active' => ['required', 'boolean']]);
        $shippingFee->update($data);
        return back()->with('success', 'Đã cập nhật phí vận chuyển.');
    }

    public function users(string $role): Response
    {
        abort_unless(in_array($role, [UserRole::CUSTOMER->value, UserRole::STAFF->value], true), 404);
        return Inertia::render($role === 'customer' ? 'admin/Customers' : 'admin/Employees', ['users' => User::query()->where('role', $role)->latest()->paginate(15)->withQueryString(), 'role' => $role]);
    }

    public function userStore(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, [UserRole::CUSTOMER->value, UserRole::STAFF->value], true), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'phone' => ['nullable', 'string', 'max:30'], 'password' => ['required', 'string', 'min:8']]);
        User::create([...$data, 'role' => $role, 'is_active' => true]);
        return back()->with('success', $role === 'staff' ? 'Đã thêm nhân viên.' : 'Đã thêm khách hàng.');
    }

    public function userUpdate(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role->value, [UserRole::CUSTOMER->value, UserRole::STAFF->value], true), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['nullable', 'string', 'max:30'], 'is_active' => ['required', 'boolean']]);
        $user->update($data);
        return back()->with('success', 'Đã cập nhật tài khoản.');
    }

    public function userDelete(User $user): RedirectResponse
    {
        abort_unless(in_array($user->role->value, [UserRole::CUSTOMER->value, UserRole::STAFF->value], true), 404);
        $user->delete();
        return back()->with('success', 'Đã xóa tài khoản.');
    }

    public function orders(): Response
    {
        return Inertia::render('admin/Orders', ['orders' => Order::query()->with('items')->latest()->paginate(15)->withQueryString()]);
    }

    public function orderApprove(Order $order): RedirectResponse
    {
        abort_unless(in_array($order->status, ['Chờ xử lý', 'Trả hàng'], true), 422, 'Đơn hàng không ở trạng thái có thể duyệt.');
        $order->update(['status' => 'Đã duyệt']);
        return back()->with('success', 'Đã duyệt đơn hàng.');
    }

    private function productRules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:product_categories,id'], 'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', 'unique:admin_products,sku'], 'brand' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'], 'old_price' => ['nullable', 'integer', 'gte:price'], 'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:2000'], 'short_description' => ['nullable', 'string', 'max:2000'], 'description' => ['nullable', 'string'],
            'specs' => ['nullable', 'array'],
        ];
    }
}
