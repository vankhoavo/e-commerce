<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Mail\OrderProcessedMail;
use App\Mail\VatInvoiceMail;
use App\Models\AdminProduct;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ShippingFee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response { $year=now()->year;$ordersByMonth=array_fill(1,12,0);$revenueByMonth=array_fill(1,12,0); Order::query()->whereYear('created_at',$year)->get(['created_at','total'])->each(function(Order $order)use(&$ordersByMonth,&$revenueByMonth):void{$month=(int)$order->created_at->month;$ordersByMonth[$month]++;$revenueByMonth[$month]+=(int)$order->total;}); return Inertia::render('admin/Dashboard',['stats'=>['orders'=>Order::count(),'revenue'=>(int)Order::sum('total'),'customers'=>User::query()->where('role',UserRole::CUSTOMER->value)->count(),'products'=>AdminProduct::count()],'charts'=>['months'=>array_map(fn(int $month):string=>'T'.$month,range(1,12)),'orders'=>array_values($ordersByMonth),'revenue'=>array_values($revenueByMonth)],'recentOrders'=>Order::query()->latest()->limit(6)->get(['id','code','customer_name','status','total','created_at'])]); }
    public function categories(): Response{return Inertia::render('admin/Categories',['categories'=>ProductCategory::query()->withCount('products')->latest()->get()]);}
    public function categoryStore(Request $request):RedirectResponse{$data=$request->validate(['name'=>['required','string','max:120'],'description'=>['nullable','string','max:1000'],'icon'=>['nullable','string','max:64']]);ProductCategory::create([...$data,'slug'=>Str::slug($data['name'])]);return back()->with('success','Đã thêm danh mục sản phẩm.');}
    public function categoryUpdate(Request $request,ProductCategory $category):RedirectResponse{$data=$request->validate(['name'=>['required','string','max:120'],'description'=>['nullable','string','max:1000'],'icon'=>['nullable','string','max:64'],'is_active'=>['required','boolean']]);$category->update([...$data,'slug'=>Str::slug($data['name'])]);return back()->with('success','Đã cập nhật danh mục.');}
    public function products():Response{return Inertia::render('admin/Products',['products'=>AdminProduct::query()->with('category:id,name')->latest()->paginate(15)->withQueryString(),'categories'=>ProductCategory::query()->orderBy('name')->get(['id','name'])]);}
    public function productStore(Request $request):RedirectResponse{$data=$request->validate($this->productRules());AdminProduct::create([...$data,'slug'=>Str::slug($data['name']).'-'.Str::lower(Str::random(5))]);return back()->with('success','Đã thêm sản phẩm.');}
    public function productUpdate(Request $request,AdminProduct $product):RedirectResponse{$rules=$this->productRules();$rules['sku']=['required','string','max:64',Rule::unique('admin_products','sku')->ignore($product->id)];$data=$request->validate($rules);$product->update([...$data,'slug'=>Str::slug($data['name']).'-'.$product->id]);return back()->with('success','Đã cập nhật sản phẩm.');}
    public function productToggle(AdminProduct $product):RedirectResponse{$product->update(['is_active'=>!$product->is_active]);return back()->with('success',$product->is_active?'Đã kích hoạt sản phẩm.':'Đã khóa sản phẩm.');}
    public function inventory():Response{$soldStatuses=['Đã duyệt','Đang giao','Đã giao','Hoàn tất'];$soldByProduct=DB::table('order_items')->join('orders','orders.id','=','order_items.order_id')->whereIn('orders.status',$soldStatuses)->select('order_items.product_id',DB::raw('SUM(order_items.quantity) as sold_quantity'))->groupBy('order_items.product_id')->pluck('sold_quantity','order_items.product_id');$products=AdminProduct::query()->with('category:id,name')->orderBy('stock')->get()->map(fn(AdminProduct $product)=>['id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku,'image'=>$product->image,'category'=>$product->category?->name??'Chưa phân loại','stock'=>(int)$product->stock,'sold'=>(int)($soldByProduct[$product->id]??$product->sold_count),'price'=>(int)$product->price,'is_active'=>(bool)$product->is_active]);return Inertia::render('admin/Inventory',['products'=>$products,'summary'=>['stockUnits'=>(int)$products->sum('stock'),'soldUnits'=>(int)$products->sum('sold'),'stockValue'=>(int)$products->sum(fn(array $p)=>$p['stock']*$p['price']),'lowStock'=>(int)$products->where('stock','<',10)->count()]]);}
    public function coupons():Response{return Inertia::render('admin/Coupons',['coupons'=>Coupon::query()->latest()->get()]);}
    public function couponStore(Request $request):RedirectResponse{$data=$request->validate(['code'=>['required','string','max:50','alpha_dash','unique:coupons,code'],'type'=>['required',Rule::in(['percent','fixed'])],'value'=>['required','integer','min:1'],'min_order_amount'=>['required','integer','min:0'],'max_discount_amount'=>['nullable','integer','min:0'],'usage_limit'=>['nullable','integer','min:1'],'starts_at'=>['nullable','date'],'ends_at'=>['nullable','date','after_or_equal:starts_at']]);if($data['type']==='percent'&&$data['value']>100)return back()->withErrors(['value'=>'Phần trăm giảm không được vượt quá 100.']);Coupon::create([...$data,'code'=>Str::upper($data['code'])]);return back()->with('success','Đã thêm mã giảm giá.');}
    public function shipping():Response{return Inertia::render('admin/Shipping',['fees'=>ShippingFee::query()->orderBy('province')->latest()->get()]);}
    public function shippingStore(Request $request):RedirectResponse{$data=$request->validate(['name'=>['required','string','max:120'],'province'=>['nullable','string','max:120'],'fee'=>['required','integer','min:0'],'free_ship_from'=>['nullable','integer','min:0']]);ShippingFee::create($data);return back()->with('success','Đã thêm cấu hình phí vận chuyển.');}
    public function shippingUpdate(Request $request,ShippingFee $shippingFee):RedirectResponse{$data=$request->validate(['name'=>['required','string','max:120'],'province'=>['nullable','string','max:120'],'fee'=>['required','integer','min:0'],'free_ship_from'=>['nullable','integer','min:0'],'is_active'=>['required','boolean']]);$shippingFee->update($data);return back()->with('success','Đã cập nhật phí vận chuyển.');}
    public function users(string $role):Response{abort_unless(in_array($role,[UserRole::CUSTOMER->value,UserRole::STAFF->value],true),404);return Inertia::render($role==='customer'?'admin/Customers':'admin/Employees',['users'=>User::query()->where('role',$role)->latest()->paginate(15)->withQueryString(),'role'=>$role]);}
    public function userStore(Request $request,string $role):RedirectResponse{abort_unless(in_array($role,[UserRole::CUSTOMER->value,UserRole::STAFF->value],true),404);$data=$request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],'phone'=>['nullable','string','max:30'],'password'=>['required','string','min:8']]);User::create([...$data,'role'=>$role,'is_active'=>true]);return back()->with('success',$role==='staff'?'Đã thêm nhân viên.':'Đã thêm khách hàng.');}
    public function userUpdate(Request $request,User $user):RedirectResponse{abort_unless(in_array($user->role->value,[UserRole::CUSTOMER->value,UserRole::STAFF->value],true),404);$data=$request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],'phone'=>['nullable','string','max:30'],'is_active'=>['required','boolean']]);$user->update($data);return back()->with('success','Đã cập nhật tài khoản.');}
    public function userDelete(User $user):RedirectResponse{abort_unless(in_array($user->role->value,[UserRole::CUSTOMER->value,UserRole::STAFF->value],true),404);$user->delete();return back()->with('success','Đã xóa tài khoản.');}
    public function orders():Response{return Inertia::render('admin/Orders',['orders'=>Order::query()->with('items')->latest()->paginate(15)->withQueryString()]);}
    public function orderApprove(Order $order):RedirectResponse
    {
        abort_unless($order->status==='Chờ xử lý',422,'Đơn hàng không ở trạng thái chờ duyệt.');
        DB::transaction(function()use($order):void{$order->load('items');foreach($order->items as $item){if(!$item->product_id)continue;$product=AdminProduct::query()->lockForUpdate()->find($item->product_id);if(!$product)abort(422,'Sản phẩm không còn tồn tại: '.$item->name);if($product->stock<$item->quantity)abort(422,'Tồn kho không đủ cho sản phẩm: '.$product->name);$product->decrement('stock',$item->quantity);$product->increment('sold_count',$item->quantity);} $order->update(['status'=>'Đã duyệt']);});

        $order->refresh()->load('user','items');
        $recipient=$order->customer_email ?: $order->user?->email;
        if ($recipient) {
            try {
                Mail::to($recipient)->send(new OrderProcessedMail($order));
            } catch (\Throwable $exception) {
                Log::warning('Không thể gửi Email đơn hàng đã xử lý.', ['order_id'=>$order->id,'recipient'=>$recipient,'error'=>$exception->getMessage()]);
            }
        }
        if ($order->vat_invoice_requested && filled($order->vat_email)) {
            try {
                Mail::to($order->vat_email)->send(new VatInvoiceMail($order));
            } catch (\Throwable $exception) {
                Log::warning('Không thể gửi Email hóa đơn VAT.', ['order_id'=>$order->id,'recipient'=>$order->vat_email,'error'=>$exception->getMessage()]);
            }
        }
        return back()->with('success',$order->vat_invoice_requested?'Đã duyệt đơn hàng, cập nhật tồn kho và gửi Email xử lý cùng hóa đơn VAT.':'Đã duyệt đơn hàng, cập nhật tồn kho và gửi Email trạng thái xử lý.');
    }

    public function administrators():Response
    {
        return Inertia::render('admin/Administrators', ['administrators'=>User::query()->where('role',UserRole::ADMIN->value)->latest()->paginate(20)->withQueryString(),'permissions'=>self::adminPermissionDefinitions()]);
    }
    public function administratorStore(Request $request):RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:255','regex:/^[^@\\s]+$/','unique:users,name'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:8'],'admin_permissions'=>['nullable','array'],'admin_permissions.*'=>['string',Rule::in(array_keys(self::adminPermissionDefinitions()))]]);
        User::create([...$data,'role'=>UserRole::ADMIN->value,'is_active'=>true,'email_verified_at'=>now(),'google_id'=>null,'admin_permissions'=>array_values($data['admin_permissions']??[])]);
        return back()->with('success','Đã tạo tài khoản quản trị viên.');
    }
    public function administratorUpdate(Request $request,User $user):RedirectResponse
    {
        abort_unless($user->role===UserRole::ADMIN,404);
        $data=$request->validate(['name'=>['required','string','max:255','regex:/^[^@\\s]+$/',Rule::unique('users','name')->ignore($user->id)],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],'password'=>['nullable','string','min:8'],'admin_permissions'=>['nullable','array'],'admin_permissions.*'=>['string',Rule::in(array_keys(self::adminPermissionDefinitions()))],'is_active'=>['required','boolean']]);
        if ($user->name==='admin') { $data['name']='admin'; $data['is_active']=true; $data['admin_permissions']=null; }
        if (array_key_exists('password',$data) && blank($data['password'])) unset($data['password']);
        $user->update($data);
        return back()->with('success','Đã cập nhật quản trị viên.');
    }
    public function administratorDelete(User $user):RedirectResponse{abort_unless($user->role===UserRole::ADMIN,404);abort_if($user->name==='admin',422,'Không thể xóa tài khoản admin gốc.');abort_if($user->id===request()->user()->id,422,'Không thể tự xóa tài khoản đang đăng nhập.');$user->delete();return back()->with('success','Đã xóa quản trị viên.');}
    public function administratorToggle(User $user):RedirectResponse{abort_unless($user->role===UserRole::ADMIN,404);abort_if($user->name==='admin',422,'Không thể khóa tài khoản admin gốc.');abort_if($user->id===request()->user()->id,422,'Không thể tự khóa tài khoản đang đăng nhập.');$user->update(['is_active'=>!$user->is_active]);return back()->with('success',$user->is_active?'Đã kích hoạt quản trị viên.':'Đã khóa quản trị viên.');}
    public static function adminPermissionDefinitions():array{return ['dashboard'=>'Tổng quan','categories'=>'Danh mục','products'=>'Sản phẩm','inventory'=>'Kho hàng','orders'=>'Đơn hàng','coupons'=>'Mã giảm giá','shipping'=>'Phí vận chuyển','customers'=>'Khách hàng','employees'=>'Nhân viên','administrators'=>'Quản trị viên'];}
    private function productRules():array{return ['category_id'=>['required','integer','exists:product_categories,id'],'name'=>['required','string','max:255'],'sku'=>['required','string','max:64','unique:admin_products,sku'],'brand'=>['nullable','string','max:100'],'price'=>['required','integer','min:0'],'old_price'=>['nullable','integer','gte:price'],'stock'=>['required','integer','min:0'],'image'=>['nullable','string','max:2000'],'short_description'=>['nullable','string','max:2000'],'description'=>['nullable','string'],'specs'=>['nullable','array']];}
}
