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
            $table->foreignId('student_id')->nullable()->after('tutor_id')->constrained('users')->onDelete('cascade');
            $table->boolean('notify_tutor')->default(true)->after('scheduled_date');
            $table->boolean('notify_jury')->default(false)->after('notify_tutor');
        });

        Schema::table('production_milestones', function (Blueprint $table) {
            $table->boolean('notify_tutor')->default(true)->after('scheduled_date');
            $table->boolean('notify_jury')->default(false)->after('notify_tutor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('period_milestones', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'notify_tutor', 'notify_jury']);
        });

        Schema::table('production_milestones', function (Blueprint $table) {
            $table->dropColumn(['notify_tutor', 'notify_jury']);
        });
    }
};
