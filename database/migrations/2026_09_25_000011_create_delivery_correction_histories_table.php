<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_correction_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('previous_bun_quantity');
            $table->unsignedInteger('previous_egg_quantity');
            $table->unsignedInteger('previous_banana_quantity');
            $table->string('previous_chalan_disk', 64);
            $table->string('previous_chalan_path');
            $table->foreignId('editor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('edited_at');

            $table->index(['delivery_id', 'edited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_correction_histories');
    }
};
