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
        Schema::create('keyword_production', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['keyword_id', 'production_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keyword_production');
    }
};
