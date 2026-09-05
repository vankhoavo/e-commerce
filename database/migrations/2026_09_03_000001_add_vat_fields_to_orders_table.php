<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('vat_invoice_requested')->default(false)->after('payment');
            $table->string('vat_company_name', 255)->nullable()->after('vat_invoice_requested');
            $table->string('vat_tax_code', 32)->nullable()->after('vat_company_name');
            $table->string('vat_address', 500)->nullable()->after('vat_tax_code');
            $table->string('vat_email', 255)->nullable()->after('vat_address');
            $table->decimal('vat_rate', 5, 2)->default(10.00)->after('vat_email');
            $table->unsignedBigInteger('vat_amount')->default(0)->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'vat_invoice_requested',
                'vat_company_name',
                'vat_tax_code',
                'vat_address',
                'vat_email',
                'vat_rate',
                'vat_amount',
            ]);
        });
    }
};
