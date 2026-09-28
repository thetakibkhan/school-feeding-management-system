<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_monthly_planning_quantities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7);
            $table->unsignedInteger('bun_quantity');
            $table->unsignedInteger('egg_quantity');
            $table->unsignedInteger('banana_quantity');
            $table->string('source_document');
            $table->timestamps();

            $table->unique(['school_id', 'month']);
            $table->index('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_monthly_planning_quantities');
    }
};
