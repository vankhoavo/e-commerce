<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (['team_invitations', 'team_members', 'teams'] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasColumn('users', 'current_team_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('current_team_id');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Team functionality has intentionally been removed and is not recreated.
    }
};
