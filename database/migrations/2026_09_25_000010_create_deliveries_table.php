<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedInteger('bun_quantity')->default(0);
            $table->unsignedInteger('egg_quantity')->default(0);
            $table->unsignedInteger('banana_quantity')->default(0);
            $table->string('chalan_disk', 64);
            $table->string('chalan_path');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'date']);
            $table->index(['created_by_user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
