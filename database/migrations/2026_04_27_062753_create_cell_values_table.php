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
        Schema::create('cell_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained()->onDelete('cascade');
            $table->foreignId('column_id')->constrained()->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cell_values');
    }
};
