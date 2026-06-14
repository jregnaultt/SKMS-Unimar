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
        Schema::create('production_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['delivery', 'defense', 'pre_defense', 'system_defense']);
            $table->string('title');
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('completed_date')->nullable();
            $table->enum('status', ['pending', 'completed', 'missed'])->default('pending');
            $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_milestones');
    }
};
