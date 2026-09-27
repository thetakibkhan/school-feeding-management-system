<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 8, 3)->nullable()->after('unit_weight_grams');
        });

        Schema::table('official_report_periods', function (Blueprint $table): void {
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('contract_number', 150)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_routing_number', 100)->nullable();
            $table->decimal('related_service_unit_price', 8, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('official_report_periods', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_number', 'invoice_date', 'contract_number',
                'bank_account_name', 'bank_account_number', 'bank_name', 'bank_branch',
                'bank_routing_number', 'related_service_unit_price',
            ]);
        });

        Schema::table('food_items', function (Blueprint $table): void {
            $table->dropColumn('unit_price');
        });
    }
};
