<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('admin_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            $table->string('brand', 100)->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('old_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('specs')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20)->default('percent');
            $table->unsignedInteger('value')->default(0);
            $table->unsignedBigInteger('min_order_amount')->default(0);
            $table->unsignedBigInteger('max_discount_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('shipping_fees', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('province', 120)->nullable();
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('free_ship_from')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_fees');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('admin_products');
        Schema::dropIfExists('product_categories');
    }
};
