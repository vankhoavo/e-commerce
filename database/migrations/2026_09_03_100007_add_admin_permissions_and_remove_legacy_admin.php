<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_permissions')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('admin_permissions')->nullable()->after('google_id');
            });
        }

        // Xóa tài khoản Admin cũ bằng email; không xóa tài khoản khách hàng cùng email.
        DB::table('users')
            ->whereRaw('LOWER(email) = ?', ['admin@techstore.local'])
            ->where('role', UserRole::ADMIN->value)
            ->delete();

        // Tạo lại tài khoản quản trị duy nhất bằng username "admin".
        $admin = DB::table('users')->whereRaw('LOWER(name) = ?', ['admin'])->where('role', UserRole::ADMIN->value)->first();
        if (! $admin) {
            DB::table('users')->insert([
                'name'=>'admin',
                'email'=>'admin@techstore.internal',
                'password'=>Hash::make('Khoa2001@'),
                'role'=>UserRole::ADMIN->value,
                'is_active'=>true,
                'google_id'=>null,
                'admin_permissions'=>null,
                'email_verified_at'=>now(),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        } else {
            DB::table('users')->where('id',$admin->id)->update([
                'email'=>'admin@techstore.internal',
                'password'=>Hash::make('Khoa2001@'),
                'google_id'=>null,
                'is_active'=>true,
                'admin_permissions'=>null,
                'email_verified_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
    }

    public function down(): void
    {
        // Không khôi phục tài khoản admin@techstore.local vì đây là tài khoản đã bị loại bỏ.
        if (Schema::hasColumn('users', 'admin_permissions')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('admin_permissions'));
        }
    }
};
