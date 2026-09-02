<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $password = (string) env('ADMIN_PASSWORD', 'Khoa2001@');

        DB::table('users')->updateOrInsert(
            ['email' => 'admin'],
            [
                'name' => 'Quản trị viên',
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin')->where('role', 'admin')->delete();
    }
};
