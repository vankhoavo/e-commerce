<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tài khoản Google này là tài khoản khách hàng, tuyệt đối không được có quyền quản trị.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereRaw('LOWER(email) = ?', ['vankhoa.work@gmail.com'])
            ->update([
                'role' => UserRole::CUSTOMER->value,
                'is_active' => true,
            ]);
    }

    /**
     * Không khôi phục quyền admin khi rollback để tránh vô tình cấp lại quyền quản trị cho tài khoản Google.
     */
    public function down(): void
    {
        // Security migration: intentionally irreversible.
    }
};
