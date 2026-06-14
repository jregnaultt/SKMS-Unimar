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
        Schema::create('oai_pmh_tokens', function (Blueprint $table) {
            $table->string('token')->primary();
            $table->timestamp('expiration_date');
            $table->integer('cursor')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oai_pmh_tokens');
    }
};
