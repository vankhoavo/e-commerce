<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('product_reviews',function(Blueprint$table):void{$table->id();$table->foreignId('product_id')->constrained('admin_products')->cascadeOnDelete();$table->foreignId('user_id')->constrained()->cascadeOnDelete();$table->foreignId('order_id')->constrained()->cascadeOnDelete();$table->unsignedTinyInteger('rating');$table->text('content');$table->boolean('is_approved')->default(true);$table->timestamps();$table->unique(['product_id','user_id','order_id']);$table->index(['product_id','is_approved']);});}public function down():void{Schema::dropIfExists('product_reviews');}};
