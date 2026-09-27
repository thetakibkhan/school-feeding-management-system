<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_report_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('form_type', 7);
            $table->char('month', 7);
            $table->string('district_name');
            $table->string('upazila_name');
            $table->string('supplier_name')->nullable();
            $table->timestamps();
            $table->unique(['form_type', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_report_periods');
    }
};
