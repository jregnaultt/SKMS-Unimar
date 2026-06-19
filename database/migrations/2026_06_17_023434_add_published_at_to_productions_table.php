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
            $table->timestamp('published_at')->nullable()->after('approval_date');
            $table->index(['workflow_state', 'published_at']);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex(['workflow_state', 'published_at']);
            $table->dropColumn('published_at');
        });
    }
};
