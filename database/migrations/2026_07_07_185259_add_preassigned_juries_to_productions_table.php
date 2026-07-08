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
        Schema::table('productions', function (Blueprint $table) {
            $table->foreignId('preassigned_jury_1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('preassigned_jury_2_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropForeign(['preassigned_jury_1_id']);
            $table->dropForeign(['preassigned_jury_2_id']);
            $table->dropColumn(['preassigned_jury_1_id', 'preassigned_jury_2_id']);
        });
    }
};
