<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('password_reset_codes', 'verified_at')) {
            Schema::table('password_reset_codes', function (Blueprint $table): void {
                $table->timestamp('verified_at')->nullable()->after('attempts');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('password_reset_codes', 'verified_at')) {
            Schema::table('password_reset_codes', function (Blueprint $table): void {
                $table->dropColumn('verified_at');
            });
        }
    }
};
