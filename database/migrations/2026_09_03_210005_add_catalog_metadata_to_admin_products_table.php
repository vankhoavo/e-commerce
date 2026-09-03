<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_products', function (Blueprint $table): void {
            $table->string('product_type', 40)->default('laptop')->after('category_id');
            $table->string('product_line', 100)->nullable()->after('product_type');
            $table->string('market_source', 40)->nullable()->after('source');
            $table->index(['product_type', 'brand']);
            $table->index(['product_line']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_products', function (Blueprint $table): void {
            $table->dropIndex(['product_type', 'brand']);
            $table->dropIndex(['product_line']);
            $table->dropColumn(['product_type', 'product_line', 'market_source']);
        });
    }
};
