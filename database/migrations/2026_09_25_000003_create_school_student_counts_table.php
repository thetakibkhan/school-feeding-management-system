<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_student_counts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('student_count');
            $table->date('effective_start_date');
            $table->timestamps();

            $table->unique(['school_id', 'effective_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_student_counts');
    }
};
