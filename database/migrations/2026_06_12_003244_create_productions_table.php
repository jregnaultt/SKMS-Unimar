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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title')->nullable();
            $table->text('abstract')->nullable();
            $table->foreignId('academic_program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('research_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('production_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('workflow_state', ['draft', 'under_review', 'needs_corrections', 'approved', 'published', 'rejected'])->default('draft');
            $table->string('doi')->nullable()->unique();
            $table->timestamp('submission_date')->nullable();
            $table->timestamp('approval_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
