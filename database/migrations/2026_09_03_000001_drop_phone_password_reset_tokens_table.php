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
        // The phone password reset flow has been removed from the application.
    }
};
