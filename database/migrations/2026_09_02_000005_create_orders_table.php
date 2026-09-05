<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('status', 32)->default('Chờ xử lý');
            $table->string('customer_name', 255);
            $table->string('customer_phone', 30);
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_address', 500);
            $table->text('note')->nullable();
            $table->string('payment', 32)->default('cod');
            $table->string('paypal_order_id', 64)->nullable()->index();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('shipping')->default(0);
            $table->unsignedBigInteger('total_shipping')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
