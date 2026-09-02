<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $admin = DB::table('users')->where(function ($query): void {
                $query->whereRaw('LOWER(name) = ?', ['admin'])
                    ->orWhereRaw('LOWER(email) = ?', ['admin@techstore.local']);
            })->orderBy('id')->first();

            if ($admin) {
                DB::table('users')->where('id', $admin->id)->update([
                    'name' => 'admin',
                    'email' => 'admin@techstore.local',
                    'password' => Hash::make('Khoa2001@'),
                    'role' => UserRole::ADMIN->value,
                    'is_active' => true,
                    'google_id' => null,
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                ]);
                return;
            }

            DB::table('users')->insert([
                'name' => 'admin',
                'email' => 'admin@techstore.local',
                'password' => Hash::make('Khoa2001@'),
                'role' => UserRole::ADMIN->value,
                'is_active' => true,
                'google_id' => null,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Không tự động xóa tài khoản quản trị khi rollback.
    }
};
