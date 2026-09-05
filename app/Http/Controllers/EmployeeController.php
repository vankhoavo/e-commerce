<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $actor=$request->user(); abort_unless($actor->isAdmin()||$actor->isSeniorStaff(),403);
        $query=User::query()->whereIn('role',array_map(static fn(UserRole $role):string=>$role->value,UserRole::employeeRoles()))->with('creator:id,name');
        if($actor->isSeniorStaff()&&!$actor->isAdmin()) $query->where('created_by_user_id',$actor->id)->whereIn('role',array_map(static fn(UserRole $role):string=>$role->value,UserRole::subordinateRoles()));
        $users=$query->latest()->paginate(20)->withQueryString()->through(static fn(User $user):array=>['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone,'role'=>$user->role->value,'role_label'=>$user->role->label(),'is_active'=>(bool)$user->is_active,'approval_status'=>$user->approval_status??'approved','created_at'=>$user->created_at,'creator'=>$user->creator?['id'=>$user->creator->id,'name'=>$user->creator->name]:null]);
        return Inertia::render('admin/Employees',['users'=>$users,'currentRole'=>$actor->isAdmin()?'admin':'senior_staff','roleOptions'=>array_map(static fn(UserRole $role):array=>['value'=>$role->value,'label'=>$role->label()],UserRole::creatableSubordinateRoles())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor=$request->user(); abort_unless($actor->isAdmin()||$actor->isSeniorStaff(),403);
        $allowedRoles=UserRole::creatableSubordinateRoles();
        $data=$request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],'phone'=>['nullable','string','max:30'],'password'=>['required','string','min:8'],'role'=>['required',Rule::in(array_map(static fn(UserRole $role):string=>$role->value,$allowedRoles))]]);
        $approved=$actor->isAdmin();
        User::create([...$data,'is_active'=>$approved,'approval_status'=>$approved?'approved':'pending','created_by_user_id'=>$actor->id,'approved_by_user_id'=>$approved?$actor->id:null,'approved_at'=>$approved?now():null,'email_verified_at'=>$approved?now():null]);
        return back()->with('success',$approved?'Đã tạo nhân viên và kích hoạt tài khoản.':'Đã tạo nhân viên và chuyển yêu cầu sang trạng thái chờ Quản trị viên phê duyệt.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor=$request->user(); abort_unless($user->role!==UserRole::CUSTOMER&&in_array($user->role,UserRole::employeeRoles(),true),404); abort_unless($actor->isAdmin()||($actor->isSeniorStaff()&&$user->created_by_user_id===$actor->id),403);
        $data=$request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],'phone'=>['nullable','string','max:30'],'password'=>['nullable','string','min:8'],'role'=>['nullable',Rule::in(array_map(static fn(UserRole $role):string=>$role->value,UserRole::creatableSubordinateRoles()))]]);
        if(blank($data['password']??null))unset($data['password']); $user->update($data); return back()->with('success','Đã cập nhật nhân viên.');
    }

    public function delete(Request $request, User $user): RedirectResponse
    {
        $actor=$request->user(); abort_unless($user->role!==UserRole::CUSTOMER&&in_array($user->role,UserRole::employeeRoles(),true),404); abort_unless($actor->isAdmin()||($actor->isSeniorStaff()&&$user->created_by_user_id===$actor->id),403); abort_if(in_array($user->role,[UserRole::SENIOR_STAFF,UserRole::STAFF],true)&&!$actor->isAdmin(),403); $user->delete(); return back()->with('success','Đã xóa nhân viên.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $actor=$request->user(); abort_unless($user->role!==UserRole::CUSTOMER&&in_array($user->role,UserRole::employeeRoles(),true),404); abort_unless($actor->isAdmin()||($actor->isSeniorStaff()&&$user->created_by_user_id===$actor->id),403); abort_if(in_array($user->role,[UserRole::SENIOR_STAFF,UserRole::STAFF],true)&&!$actor->isAdmin(),403); $user->update(['is_active'=>!$user->is_active]); return back()->with('success',$user->is_active?'Đã kích hoạt nhân viên.':'Đã khóa nhân viên.');
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(),403); abort_unless(in_array($user->role,UserRole::subordinateRoles(),true),404); abort_unless($user->approval_status==='pending',422,'Nhân viên này không còn ở trạng thái chờ phê duyệt.'); $user->update(['is_active'=>true,'approval_status'=>'approved','approved_by_user_id'=>$request->user()->id,'approved_at'=>now(),'email_verified_at'=>$user->email_verified_at??now()]); return back()->with('success','Đã phê duyệt nhân viên và kích hoạt tài khoản.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(),403); abort_unless(in_array($user->role,UserRole::subordinateRoles(),true),404); abort_unless($user->approval_status==='pending',422,'Nhân viên này không còn ở trạng thái chờ phê duyệt.'); $user->update(['is_active'=>false,'approval_status'=>'rejected','approved_by_user_id'=>$request->user()->id,'approved_at'=>now()]); return back()->with('success','Đã từ chối yêu cầu tạo nhân viên.');
    }
}
