<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // Chuyển đúng tài khoản quản trị viên mẫu đang hiển thị trên trang quản trị
        // thành Nhân viên cấp cao. Không tác động đến tài khoản admin gốc.
        DB::table('users')
            ->where('role', 'admin')
            ->where('name', 'Quản trị viên')
            ->where('email', 'admin@techstore.internal')
            ->update([
                'role' => 'senior_staff',
                'admin_permissions' => null,
                'approval_status' => 'approved',
                'approved_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('role', 'senior_staff')
            ->where('name', 'Quản trị viên')
            ->where('email', 'admin@techstore.internal')
            ->update([
                'role' => 'admin',
                'updated_at' => now(),
            ]);
    }
};
