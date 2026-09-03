<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\BackofficeRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BackofficeRoleController
{
    public function index(Request $request, string $role): Response
    {
        abort_unless(in_array($role,[UserRole::SALES->value,UserRole::TECHNICAL->value,UserRole::CUSTOMER_SERVICE->value],true),404);
        return Inertia::render('admin/BackofficeRole',[
            'role'=>$role,
            'roleLabel'=>UserRole::from($role)->label(),
            'users'=>User::query()->where('role',$role)->latest()->paginate(20)->withQueryString(),
            'pendingRequests'=>$request->user()->isAdmin() ? BackofficeRoleRequest::query()->where('role',$role)->where('status','pending')->with('requester:id,name,email')->latest()->get()->map(fn(BackofficeRoleRequest $r)=>['id'=>$r->id,'name'=>$r->name,'email'=>$r->email,'phone'=>$r->phone,'requester'=>$r->requester?->only(['id','name','email']),'createdAt'=>$r->created_at?->toIso8601String()]) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['role'=>['required',Rule::in([UserRole::SALES->value,UserRole::TECHNICAL->value,UserRole::CUSTOMER_SERVICE->value])],'name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],'phone'=>['nullable','string','max:30'],'password'=>['required','string','min:8'],'admin_password'=>['required','string']]);
        abort_unless(Hash::check($data['admin_password'],$request->user()->password),422,'Mật khẩu xác thực quản trị viên không chính xác.');
        $payload=['requested_by'=>$request->user()->id,'role'=>$data['role'],'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'password_hash'=>Hash::make($data['password'])];
        if($request->user()->isAdmin() && $request->user()->name==='admin'){
            User::create(['name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'password'=>$data['password'],'role'=>$data['role'],'is_active'=>true,'backoffice_title'=>UserRole::from($data['role'])->label()]);
            return back()->with('success','Đã tạo tài khoản '.UserRole::from($data['role'])->label().' và kích hoạt.');
        }
        BackofficeRoleRequest::create($payload);
        return back()->with('success','Đã gửi yêu cầu tạo tài khoản đến Admin phê duyệt.');
    }

    public function approve(Request $request, BackofficeRoleRequest $roleRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() && $request->user()->name==='admin',403,'Chỉ Admin gốc được phê duyệt tài khoản nhân sự.');
        abort_unless($roleRequest->status==='pending',422,'Yêu cầu đã được xử lý.');
        User::create(['name'=>$roleRequest->name,'email'=>$roleRequest->email,'phone'=>$roleRequest->phone,'password'=>$roleRequest->password_hash,'role'=>$roleRequest->role,'is_active'=>true,'backoffice_title'=>UserRole::from($roleRequest->role)->label()]);
        $roleRequest->update(['status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()]);
        return back()->with('success','Đã phê duyệt và kích hoạt tài khoản nhân sự.');
    }

    public function reject(Request $request, BackofficeRoleRequest $roleRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() && $request->user()->name==='admin',403);
        $roleRequest->update(['status'=>'rejected','approved_by'=>$request->user()->id,'rejected_at'=>now(),'reason'=>$request->string('reason')->toString()]);
        return back()->with('success','Đã từ chối yêu cầu tài khoản nhân sự.');
    }
}
