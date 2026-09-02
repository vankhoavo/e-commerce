<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'address_province')) {
                $table->string('address_province', 120)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'address_ward')) {
                $table->string('address_ward', 160)->nullable()->after('address_province');
            }
            if (! Schema::hasColumn('users', 'address_detail')) {
                $table->string('address_detail', 250)->nullable()->after('address_ward');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'address_detail') ? 'address_detail' : null,
                Schema::hasColumn('users', 'address_ward') ? 'address_ward' : null,
                Schema::hasColumn('users', 'address_province') ? 'address_province' : null,
            ]));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
