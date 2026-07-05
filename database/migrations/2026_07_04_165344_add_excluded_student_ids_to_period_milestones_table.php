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
        Schema::table('period_milestones', function (Blueprint $table) {
            $table->json('excluded_student_ids')->nullable()->after('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('period_milestones', function (Blueprint $table) {
            $table->dropColumn('excluded_student_ids');
        });
    }
};
