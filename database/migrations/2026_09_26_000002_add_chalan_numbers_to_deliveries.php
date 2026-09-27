<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->string('chalan_number', 100)->nullable()->after('banana_quantity');
        });

        Schema::table('delivery_correction_histories', function (Blueprint $table): void {
            $table->string('previous_chalan_number', 100)->nullable()->after('previous_banana_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_correction_histories', function (Blueprint $table): void {
            $table->dropColumn('previous_chalan_number');
        });

        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropColumn('chalan_number');
        });
    }
};
