<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_ten_invoice_sequences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        DB::table('form_ten_invoice_sequences')->insert([
            'id' => 1,
            'last_number' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('form_ten_invoice_sequences');
    }
};
