<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->index()->after('role');
            }
            if (! Schema::hasColumn('users', 'approved_by_user_id')) {
                $table->unsignedBigInteger('approved_by_user_id')->nullable()->index()->after('created_by_user_id');
            }
            if (! Schema::hasColumn('users', 'approval_status')) {
                $table->string('approval_status', 20)->default('approved')->index()->after('approved_by_user_id');
            }
            if (! Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('users')->where('role', 'staff')->update([
            'role' => 'senior_staff',
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('users', 'approval_status')) {
                $table->dropIndex(['approval_status']);
            }
            if (Schema::hasColumn('users', 'approved_by_user_id')) {
                $table->dropIndex(['approved_by_user_id']);
            }
            if (Schema::hasColumn('users', 'created_by_user_id')) {
                $table->dropIndex(['created_by_user_id']);
            }
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'approved_at') ? 'approved_at' : null,
                Schema::hasColumn('users', 'approval_status') ? 'approval_status' : null,
                Schema::hasColumn('users', 'approved_by_user_id') ? 'approved_by_user_id' : null,
                Schema::hasColumn('users', 'created_by_user_id') ? 'created_by_user_id' : null,
            ]));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
