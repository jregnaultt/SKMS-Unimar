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
        Schema::table('production_milestones', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('document_version_id');
            $table->foreignId('period_milestone_id')->nullable()->after('google_event_id')->constrained('period_milestones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_milestones', function (Blueprint $table) {
            $table->dropForeign(['period_milestone_id']);
            $table->dropColumn('period_milestone_id');
            $table->dropColumn('google_event_id');
        });
    }
};
