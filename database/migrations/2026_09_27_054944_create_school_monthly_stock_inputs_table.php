<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_monthly_stock_inputs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7);
            $table->unsignedInteger('boy_count')->nullable();
            $table->unsignedInteger('girl_count')->nullable();
            $table->string('union_name')->nullable();
            $table->string('cluster_name')->nullable();
            foreach (['bun', 'egg', 'banana', 'biscuit', 'milk'] as $item) {
                $table->unsignedInteger($item.'_opening')->nullable();
                if (in_array($item, ['biscuit', 'milk'], true)) {
                    $table->unsignedInteger($item.'_received')->nullable();
                }
                $table->unsignedInteger($item.'_distributed')->nullable();
            }
            $table->timestamps();
            $table->unique(['school_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_monthly_stock_inputs');
    }
};
