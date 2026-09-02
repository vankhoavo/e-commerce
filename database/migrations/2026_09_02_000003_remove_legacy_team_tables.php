<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the foreign key before dropping users.current_team_id.
        // MySQL does not allow the column to be dropped while the FK still exists,
        // even when foreign-key checks are temporarily disabled.
        if (Schema::hasColumn('users', 'current_team_id')) {
            $foreignKeyExists = DB::selectOne(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'users'
                   AND COLUMN_NAME = 'current_team_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );

            Schema::table('users', function (Blueprint $table) use ($foreignKeyExists): void {
                if ($foreignKeyExists !== null) {
                    $table->dropForeign(['current_team_id']);
                }

                $table->dropColumn('current_team_id');
            });
        }

        // Drop child tables before the legacy teams table so their foreign keys
        // cannot prevent teams from being removed.
        foreach (['team_invitations', 'team_members', 'teams'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Team functionality has intentionally been removed and is not recreated.
    }
};
