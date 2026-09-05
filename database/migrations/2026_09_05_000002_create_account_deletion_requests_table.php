<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_deletion_requests')) {
            Schema::create('account_deletion_requests', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamps();
                $table->index(['status', 'requested_at']);
            });
            return;
        }

        $columnsToAdd = [];
        if (! Schema::hasColumn('account_deletion_requests', 'reason')) $columnsToAdd[] = 'reason';
        if (! Schema::hasColumn('account_deletion_requests', 'reviewed_by_user_id')) $columnsToAdd[] = 'reviewed_by_user_id';
        if (! Schema::hasColumn('account_deletion_requests', 'requested_at')) $columnsToAdd[] = 'requested_at';
        if (! Schema::hasColumn('account_deletion_requests', 'reviewed_at')) $columnsToAdd[] = 'reviewed_at';
        if (! Schema::hasColumn('account_deletion_requests', 'review_note')) $columnsToAdd[] = 'review_note';

        if ($columnsToAdd) {
            Schema::table('account_deletion_requests', function (Blueprint $table) use ($columnsToAdd): void {
                if (in_array('reason', $columnsToAdd, true)) $table->text('reason')->nullable()->after('user_id');
                if (in_array('reviewed_by_user_id', $columnsToAdd, true)) $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('status');
                if (in_array('requested_at', $columnsToAdd, true)) $table->timestamp('requested_at')->nullable()->after('reviewed_by_user_id');
                if (in_array('reviewed_at', $columnsToAdd, true)) $table->timestamp('reviewed_at')->nullable()->after('requested_at');
                if (in_array('review_note', $columnsToAdd, true)) $table->text('review_note')->nullable()->after('reviewed_at');
            });
        }

        if (Schema::hasColumn('account_deletion_requests', 'reviewed_by_user_id')) {
            $foreignKeyExists = collect(Schema::getForeignKeys('account_deletion_requests'))
                ->contains(fn (array $foreignKey): bool => in_array('reviewed_by_user_id', $foreignKey['columns'] ?? [], true));
            if (! $foreignKeyExists) {
                Schema::table('account_deletion_requests', function (Blueprint $table): void {
                    $table->foreign('reviewed_by_user_id')->references('id')->on('users')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Keep the table and existing data intact on rollback.
    }
};
