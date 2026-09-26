<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->date('chalan_date')->nullable()->after('chalan_number');
        });

        Schema::table('delivery_correction_histories', function (Blueprint $table): void {
            $table->date('previous_chalan_date')->nullable()->after('previous_chalan_number');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_correction_histories', function (Blueprint $table): void {
            $table->dropColumn('previous_chalan_date');
        });

        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropColumn('chalan_date');
        });
    }
};
