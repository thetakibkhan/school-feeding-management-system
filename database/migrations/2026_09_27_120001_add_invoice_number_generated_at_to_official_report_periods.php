<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('official_report_periods', function (Blueprint $table): void {
            $table->timestamp('invoice_number_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('official_report_periods', function (Blueprint $table): void {
            $table->dropColumn('invoice_number_generated_at');
        });
    }
};
