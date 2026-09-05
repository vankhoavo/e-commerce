<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdministratorController extends Controller
{
    public function index(): Response
    {
        $approvals = User::query()
            ->whereIn('role', array_map(static fn (UserRole $role): string => $role->value, UserRole::subordinateRoles()))
            ->where('approval_status', 'pending')
            ->with('creator:id,name')
            ->latest()->get()
            ->map(static fn (User $user): array => [
                'id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone,
                'role'=>$user->role->value,'role_label'=>$user->role->label(),
                'creator'=>$user->creator ? ['id'=>$user->creator->id,'name'=>$user->creator->name] : null,
                'created_at'=>$user->created_at,
            ]);

        $seniorStaff = User::query()
            ->whereIn('role', [UserRole::SENIOR_STAFF->value, UserRole::STAFF->value])
            ->with('creator:id,name')->latest()->get()
            ->map(static fn (User $user): array => self::employeePayload($user));

        $staff = User::query()
            ->whereIn('role', array_map(static fn (UserRole $role): string => $role->value, UserRole::subordinateRoles()))
            ->with('creator:id,name')->latest()->get()
            ->map(static fn (User $user): array => self::employeePayload($user));

        return Inertia::render('admin/Administrators', [
            'administrators'=>User::query()->where('role',UserRole::ADMIN->value)->latest()->paginate(20)->withQueryString(),
            'permissions'=>self::permissionDefinitions(),
            'employeeApprovals'=>$approvals,'seniorStaff'=>$seniorStaff,'staff'=>$staff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:255','regex:/^[^@\\s]+$/','unique:users,name'],
            'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:8'],
            'admin_permissions'=>['nullable','array'],'admin_permissions.*'=>['string',Rule::in(array_keys(self::permissionDefinitions()))],
        ]);
        User::create([...$data,'role'=>UserRole::ADMIN->value,'is_active'=>true,'email_verified_at'=>now(),'google_id'=>null,
            'admin_permissions'=>array_values($data['admin_permissions']??[]),'approval_status'=>'approved',
            'approved_by_user_id'=>$request->user()->id,'approved_at'=>now()]);
        return back()->with('success','Đã tạo tài khoản quản trị viên.');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],
            'phone'=>['nullable','string','max:30'],'password'=>['required','string','min:8'],
            'role'=>['required',Rule::in(array_map(static fn(UserRole $role):string=>$role->value,UserRole::creatableSubordinateRoles()))],
        ]);
        User::create([...$data,'is_active'=>true,'approval_status'=>'approved','created_by_user_id'=>$request->user()->id,
            'approved_by_user_id'=>$request->user()->id,'approved_at'=>now(),'email_verified_at'=>now()]);
        return back()->with('success','Đã tạo nhân viên và kích hoạt tài khoản.');
    }

    public function storeSenior(Request $request): RedirectResponse
    {
        $this->verifyAdminPassword($request);
        $data=$request->validate([
            'name'=>['required','string','max:255','regex:/^[^@\\s]+$/','unique:users,name'],
            'email'=>['required','email','max:255','unique:users,email'],'phone'=>['nullable','string','max:30'],
            'password'=>['required','string','min:8'],
        ]);
        User::create([...$data,'role'=>UserRole::SENIOR_STAFF->value,'is_active'=>true,'approval_status'=>'approved',
            'created_by_user_id'=>$request->user()->id,'approved_by_user_id'=>$request->user()->id,'approved_at'=>now(),'email_verified_at'=>now()]);
        return back()->with('success','Đã tạo tài khoản Nhân viên cấp cao.');
    }

    public function updateSenior(Request $request, User $user): RedirectResponse
    {
        $this->verifyAdminPassword($request);
        abort_unless(in_array($user->role,[UserRole::SENIOR_STAFF,UserRole::STAFF],true),404);
        $data=$request->validate([
            'name'=>['required','string','max:255','regex:/^[^@\\s]+$/',Rule::unique('users','name')->ignore($user->id)],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],
            'phone'=>['nullable','string','max:30'],'password'=>['nullable','string','min:8'],'is_active'=>['required','boolean'],
        ]);
        if(blank($data['password']??null)) unset($data['password']);
        $user->update($data);
        return back()->with('success','Đã cập nhật Nhân viên cấp cao.');
    }

    public function toggleSenior(Request $request, User $user): RedirectResponse
    {
        $this->verifyAdminPassword($request);
        abort_unless(in_array($user->role,[UserRole::SENIOR_STAFF,UserRole::STAFF],true),404);
        abort_if($user->id===$request->user()->id,422,'Không thể tự khóa tài khoản đang đăng nhập.');
        $user->update(['is_active'=>!$user->is_active]);
        return back()->with('success',$user->is_active?'Đã kích hoạt Nhân viên cấp cao.':'Đã khóa Nhân viên cấp cao.');
    }

    public function destroySenior(Request $request, User $user): RedirectResponse
    {
        $this->verifyAdminPassword($request);
        abort_unless(in_array($user->role,[UserRole::SENIOR_STAFF,UserRole::STAFF],true),404);
        abort_if($user->id===$request->user()->id,422,'Không thể tự xóa tài khoản đang đăng nhập.');
        $user->delete();
        return back()->with('success','Đã xóa Nhân viên cấp cao.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role===UserRole::ADMIN,404);
        $data=$request->validate(['name'=>['required','string','max:255','regex:/^[^@\\s]+$/',Rule::unique('users','name')->ignore($user->id)],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],'password'=>['nullable','string','min:8'],
            'admin_permissions'=>['nullable','array'],'admin_permissions.*'=>['string',Rule::in(array_keys(self::permissionDefinitions()))],'is_active'=>['required','boolean']]);
        if($user->name==='admin'){$data['name']='admin';$data['is_active']=true;$data['admin_permissions']=null;}
        if(array_key_exists('password',$data)&&blank($data['password']))unset($data['password']);
        $user->update($data); return back()->with('success','Đã cập nhật quản trị viên.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_unless($user->role===UserRole::ADMIN,404); abort_if($user->name==='admin',422,'Không thể khóa tài khoản admin gốc.');
        abort_if($user->id===request()->user()->id,422,'Không thể tự khóa tài khoản đang đăng nhập.');
        $user->update(['is_active'=>!$user->is_active]); return back()->with('success',$user->is_active?'Đã kích hoạt quản trị viên.':'Đã khóa quản trị viên.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role===UserRole::ADMIN,404); abort_if($user->name==='admin',422,'Không thể xóa quản trị viên gốc.');
        abort_if($user->id===request()->user()->id,422,'Không thể tự xóa tài khoản đang đăng nhập.'); $user->delete();
        return back()->with('success','Đã xóa quản trị viên.');
    }

    private function verifyAdminPassword(Request $request): void
    {
        $password=(string)$request->input('admin_password');
        abort_unless($request->user()?->isAdmin() && $password!=='' && Hash::check($password,$request->user()->password),422,'Mật khẩu Quản trị viên không đúng.');
    }

    private static function employeePayload(User $user): array
    {
        return ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone,'role'=>$user->role->value,
            'role_label'=>$user->role->label(),'is_active'=>(bool)$user->is_active,'approval_status'=>$user->approval_status??'approved',
            'created_at'=>$user->created_at,'creator'=>$user->creator?['id'=>$user->creator->id,'name'=>$user->creator->name]:null];
    }

    public static function permissionDefinitions(): array
    {
        return ['dashboard'=>'Tổng quan','categories'=>'Danh mục','products'=>'Sản phẩm','inventory'=>'Kho hàng','orders'=>'Đơn hàng','coupons'=>'Mã giảm giá','shipping'=>'Phí vận chuyển','customers'=>'Khách hàng','employees'=>'Nhân viên cấp cao','administrators'=>'Quản trị viên'];
    }
}
