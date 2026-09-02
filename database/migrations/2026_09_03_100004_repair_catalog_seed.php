<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('admin_products')->count() > 0) return;

        $migration = require database_path('migrations/2026_09_03_100003_sync_storefront_catalog.php');
        $migration->down();
        $migration->up();
    }

    public function down(): void
    {
        // The catalog migration owns the seeded catalog data.
    }
};
