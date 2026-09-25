<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ration_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('food_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('ration_grams');
            $table->date('effective_start_date');
            $table->timestamps();

            $table->unique(['food_item_id', 'effective_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ration_settings');
    }
};
