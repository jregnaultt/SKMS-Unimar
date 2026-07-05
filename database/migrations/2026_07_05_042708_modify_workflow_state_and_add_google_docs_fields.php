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
            $table->string('workflow_state')->default('draft')->change();
            $table->boolean('jury_review_requested')->default(false)->after('workflow_state');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->string('google_comment_id')->nullable()->unique()->after('parent_id');
            $table->string('google_reply_id')->nullable()->unique()->after('google_comment_id');
            $table->boolean('resolved_in_google')->default(false)->after('google_reply_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['google_comment_id', 'google_reply_id', 'resolved_in_google']);
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropColumn('jury_review_requested');
            $table->enum('workflow_state', ['draft', 'under_review', 'needs_corrections', 'approved', 'published', 'rejected'])->default('draft')->change();
        });
    }
};
