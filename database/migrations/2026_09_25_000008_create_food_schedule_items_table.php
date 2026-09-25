<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_schedule_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('food_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['food_schedule_id', 'food_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_schedule_items');
    }
};
