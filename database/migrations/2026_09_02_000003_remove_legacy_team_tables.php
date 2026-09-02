<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'current_team_id')) {
            if (DB::getDriverName() === 'mysql') {
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
            } else {
                Schema::table('users', function (Blueprint $table): void {
                    $table->dropColumn('current_team_id');
                });
            }
        }

        foreach (['team_invitations', 'team_members', 'teams'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Team functionality has intentionally been removed and is not recreated.
    }
};
