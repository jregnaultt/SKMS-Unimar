<?php

namespace App\Console\Commands;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Keyword;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\Subject;
use App\Services\MetadataExtractorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;

class ImportHistoricalTdi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-historical-tdi {--dry-run : Run the import in dry-run mode (no database changes, only preview)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports historical TDI theses from context/tesispruebas/TDI-INGENIERÍA/2025, extracting periods, publication dates, and other metadata.';

    /**
     * Execute the console command.
     */
    public function handle(MetadataExtractorService $extractorService): int
    {
        config(['media-library.max_file_size' => 1024 * 1024 * 50]); // Increase to 50MB for bulk import

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->info('=== DRY RUN MODE: No modifications will be written to the database ===');
        }

        $dir = '/var/www/html/context/tesispruebas/TDI-INGENIERÍA/2025';
        if (! is_dir($dir)) {
            $this->error("Directory not found in container: $dir");

            return 1;
        }

        $files = glob($dir.'/*.pdf');
        if (empty($files)) {
            $this->warn("No PDF files found in: $dir");

            return 0;
        }

        $this->info('Found '.count($files).' PDF files to import.');

        // Months map for Spanish dates
        $monthsMap = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
        ];

        // Ensure default academic program (Ingeniería de Sistemas) exists
        $program = AcademicProgram::where('code', 'ING-SIS')->first();
        if (! $program) {
            $this->error("Academic program 'Ingeniería de Sistemas' (ING-SIS) not found in database.");

            return 1;
        }

        // Ensure production type 'Tesis de Grado' exists, fallback to 'Trabajo de Investigación'
        $type = ProductionType::where('name', 'Tesis de Grado')->first()
             ?? ProductionType::where('name', 'Trabajo de Investigación')->first();
        if (! $type) {
            $this->error('No valid ProductionType (Tesis de Grado / Trabajo de Investigación) found in database.');

            return 1;
        }

        // Get research lines for Systems Engineering
        $lineIa = ResearchLine::where('academic_program_id', $program->id)->where('name', 'Inteligencia Artificial')->first();
        $lineSoftware = ResearchLine::where('academic_program_id', $program->id)->where('name', 'Desarrollo de Software')->first();

        if (! $lineSoftware) {
            $this->error("Research line 'Desarrollo de Software' not found for systems engineering.");

            return 1;
        }

        $importedCount = 0;
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $filePath) {
            $filename = basename($filePath);

            // 1. Parse filename for period and year
            if (! preg_match('/^IS(\d+)(20\d{2})-(I+|IISANCHEZ)\b/i', $filename, $matches)) {
                $this->error("\nFailed to parse filename structure for: $filename. Skipping.");

                continue;
            }

            $code = $matches[1];
            $year = $matches[2];
            $periodSuffix = $matches[3] === 'IISANCHEZ' ? 'II' : $matches[3];
            $periodName = "{$year}-{$periodSuffix}";

            // 2. Fetch or create AcademicPeriod
            $academicPeriod = null;
            if (! $dryRun) {
                $academicPeriod = AcademicPeriod::firstOrCreate(
                    ['name' => $periodName],
                    [
                        'start_date' => $periodSuffix === 'II' ? "{$year}-07-01" : "{$year}-09-01",
                        'end_date' => "{$year}-12-31",
                        'is_active' => true,
                    ]
                );
            }

            // 3. Extract text from PDF first pages
            try {
                $extractorService->removeExtraUnimarCoverPage($filePath);
                $text = (new Pdf('/usr/bin/pdftotext'))
                    ->setPdf($filePath)
                    ->setOptions(['f 1', 'l 2'])
                    ->text();
            } catch (\Exception $e) {
                $this->error("\nFailed to extract text from PDF $filename: ".$e->getMessage().'. Skipping.');

                continue;
            }

            $cleanText = preg_replace('/\s+/', ' ', $text);

            // 4. Extract Publication Date using Cover Page regex scan
            $datePattern = '/(?:\b(\d{1,2})\s+de(?:l)?\s+)?\b(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\b(?:\s+de(?:l)?)?\s+(\d{4})\b/ui';
            $pubDate = null;
            if (preg_match($datePattern, $cleanText, $dateMatches)) {
                $day = ! empty($dateMatches[1]) ? str_pad($dateMatches[1], 2, '0', STR_PAD_LEFT) : '15'; // Default to 15th
                $monthWord = strtolower($dateMatches[2]);
                $month = $monthsMap[$monthWord] ?? '01';
                $dateYear = $dateMatches[3];
                $pubDate = "{$dateYear}-{$month}-{$day} 00:00:00";
            } else {
                // Fallback to period-based date
                $fallbackMonth = ($periodSuffix === 'II') ? '07' : '12';
                $pubDate = "{$year}-{$fallbackMonth}-15 00:00:00";
            }

            // 5. Extract metadata via extractor service
            $metadata = $extractorService->extractMetadata($filePath);
            $title = $metadata['title'] ?? null;
            $abstract = $metadata['abstract'] ?? null;
            $authors = $metadata['authors'] ?? null;
            $tutor = $metadata['tutor'] ?? null;
            $keywordsString = $metadata['keywords'] ?? null;

            // Fallback values if metadata extraction was partial
            if (empty($title) || $title === '? No encontrado') {
                $title = 'Trabajo Especial de Grado - '.$filename;
            }
            if (empty($abstract) || $abstract === '? No encontrado') {
                $abstract = 'Resumen no disponible en los metadatos extraídos.';
            }
            if (empty($authors) || $authors === '? No encontrado') {
                // Parse author from filename (e.g. IS1032025-II AGUILAR.pdf -> AGUILAR)
                $authorPart = preg_replace('/^IS\d+20\d{2}-(?:I+|IISANCHEZ)\s+/i', '', pathinfo($filename, PATHINFO_FILENAME));
                $authors = $extractorService->cleanName($authorPart);
            }
            if (empty($tutor) || $tutor === '? No encontrado') {
                $tutor = 'Tutor no registrado';
            }

            // Check if already imported
            if (Production::where('title', $title)->exists()) {
                $this->info("\n[Skipped] Already imported: $filename");
                $bar->advance();

                continue;
            }

            // 6. Map subject based on cover text
            $subjectId = 3; // Default to Trabajo de Investigación II
            if (stripos($cleanText, 'SEMINARIO METODOLÓGICO') !== false) {
                $subjectId = 1; // ...
            } elseif (stripos($cleanText, 'Trabajo de Investigación I') !== false) {
                $subjectId = 2; // TDI I
            } elseif (stripos($cleanText, 'Trabajo de Investigación II') !== false || stripos($cleanText, 'Trabajo de Grado II') !== false) {
                $subjectId = 3; // TDI II / Trabajo de Grado II
            }

            // 7. Map research line based on keyword analysis in title/abstract
            $researchLineId = $lineSoftware->id; // Default
            $aiKeywords = ['inteligencia artificial', 'artificial intelligence', 'ia', 'redes neuronales', 'deep learning', 'machine learning', 'chatbot', 'procesamiento de lenguaje', 'nlp', 'visión artificial'];
            $textForAnalysis = strtolower($title.' '.$abstract);
            foreach ($aiKeywords as $aiKw) {
                if (str_contains($textForAnalysis, $aiKw)) {
                    if ($lineIa) {
                        $researchLineId = $lineIa->id;
                    }
                    break;
                }
            }

            if ($dryRun) {
                $this->line('');
                $this->info("Previewing Import for: $filename");
                $this->line(" - Title: $title");
                $this->line(" - Authors: $authors");
                $this->line(" - Tutor: $tutor");
                $this->line(" - Period: $periodName");
                $this->line(" - Subject ID: $subjectId");
                $this->line(" - Research Line ID: $researchLineId");
                $this->line(" - Publication Date: $pubDate");
                $this->line(' - Keywords: '.($keywordsString ?? 'None'));
                $this->line('------------------------------------------------');
            } else {
                // 8. DB transaction to insert the production
                try {
                    DB::transaction(function () use ($title, $abstract, $authors, $tutor, $program, $researchLineId, $type, $academicPeriod, $subjectId, $pubDate, $keywordsString, $filePath) {
                        $production = Production::create([
                            'uuid' => (string) Str::uuid(),
                            'title' => $title,
                            'abstract' => $abstract,
                            'authors' => $authors,
                            'tutor' => $tutor,
                            'academic_program_id' => $program->id,
                            'research_line_id' => $researchLineId,
                            'production_type_id' => $type->id,
                            'academic_period_id' => $academicPeriod->id,
                            'subject_id' => $subjectId,
                            'workflow_state' => 'published',
                            'submission_date' => $pubDate,
                            'approval_date' => $pubDate,
                            'published_at' => $pubDate,
                        ]);

                        // Sync keywords
                        if (! empty($keywordsString) && $keywordsString !== '? No encontrado') {
                            $keywords = array_filter(
                                array_map('trim', explode(',', $keywordsString)),
                                fn ($k) => strlen($k) > 0
                            );

                            $keywordIds = [];
                            foreach ($keywords as $kwName) {
                                $keyword = Keyword::firstOrCreate(['name' => $kwName]);
                                $keywordIds[] = $keyword->id;
                            }
                            $production->keywords()->sync($keywordIds);
                        }

                        // Attach the PDF file
                        $production->addMedia($filePath)
                            ->preservingOriginal()
                            ->toMediaCollection('documento');
                    });
                    $importedCount++;
                } catch (\Exception $e) {
                    $this->error("\nDatabase transaction failed for $filename: ".$e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Dry run finished. No records were created.');
        } else {
            $this->info("Import finished! Successfully imported $importedCount / ".count($files).' thesis works.');
        }

        return 0;
    }
}
