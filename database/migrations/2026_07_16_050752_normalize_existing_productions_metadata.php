<?php

use App\Services\MetadataExtractorService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $productions = DB::table('productions')->get();
        $extractor = app(MetadataExtractorService::class);

        foreach ($productions as $p) {
            $newTitle = $p->title;

            // 1. Fix title leaks where "Tutor:" was appended
            if (preg_match('/^(.*?)\s+Tutor:\s*(.*)$/iu', $newTitle, $matches)) {
                $newTitle = trim($matches[1]);
            }

            // Strip leading/trailing backticks and extra spaces
            $newTitle = trim($newTitle, '` ');

            // 2. Normalize tutor name
            $newTutor = $p->tutor ? $extractor->normalizeName($p->tutor) : $p->tutor;

            // 3. Normalize author name(s)
            $newAuthors = $p->authors ? $extractor->normalizeName($p->authors) : $p->authors;

            DB::table('productions')->where('id', $p->id)->update([
                'title' => $newTitle,
                'tutor' => $newTutor,
                'authors' => $newAuthors,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or we can restore if we kept backup, but metadata cleaning is generally one-way.
    }
};
