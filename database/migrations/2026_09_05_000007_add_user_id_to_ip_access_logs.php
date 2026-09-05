<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ip_access_logs', 'user_id')) {
            Schema::table('ip_access_logs', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ip_access_logs', 'user_id')) {
            Schema::table('ip_access_logs', function (Blueprint $table): void {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
