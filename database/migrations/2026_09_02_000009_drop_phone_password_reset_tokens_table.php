<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('phone_password_reset_tokens');
    }

    public function down(): void
    {
        // The removed phone password reset feature is intentionally not restored.
    }
};
