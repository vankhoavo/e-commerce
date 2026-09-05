<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->unsignedTinyInteger('ip_version')->nullable();
            $table->string('ip_type', 30)->nullable();
            $table->string('ipv6', 45)->nullable();
            $table->string('organization')->nullable();
            $table->string('asn', 32)->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 8)->nullable()->index();
            $table->string('continent')->nullable();
            $table->string('device', 40)->nullable();
            $table->string('browser', 80)->nullable();
            $table->string('operating_system', 80)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ptr')->nullable();
            $table->boolean('is_proxy')->nullable();
            $table->boolean('is_vpn')->nullable();
            $table->boolean('is_tor')->nullable();
            $table->boolean('is_mobile')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_access_logs');
    }
};
