<?php

namespace App\Console\Commands;

use App\Services\MetadataExtractorService;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Console\Command;
use Spatie\PdfToText\Pdf;

class TestMetadataExtraction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-metadata-extraction {dir? : The directory containing PDF and DOCX files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs metadata extraction on a folder of PDF and DOCX files and outputs a report';

    /**
     * Execute the console command.
     */
    public function handle(MetadataExtractorService $extractor): int
    {
        $dir = $this->argument('dir') ?? base_path('context/tesispruebas');

        if (! is_dir($dir)) {
            $this->error("Directory not found: $dir");

            return 1;
        }

        $this->info("Scanning directory: $dir");

        $files = [];
        try {
            $directoryIterator = new \RecursiveDirectoryIterator($dir);
            $iterator = new \RecursiveIteratorIterator($directoryIterator);

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = strtolower($file->getExtension());
                    if (in_array($extension, ['pdf', 'docx'])) {
                        $files[] = $file->getRealPath();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('Error reading directory: '.$e->getMessage());

            return 1;
        }

        if (empty($files)) {
            $this->warn("No PDF or DOCX files found in: $dir");

            return 0;
        }

        $this->info('Found '.count($files).' files to test. Starting batch extraction...');

        $results = [];
        $summary = [
            'total' => 0,
            'title_ok' => 0,
            'authors_ok' => 0,
            'tutor_ok' => 0,
            'abstract_ok' => 0,
            'keywords_ok' => 0,
            'errors' => 0,
        ];

        $csvRows = [];
        // CSV Header
        $csvRows[] = ['Archivo', 'Estado', 'Título', 'Autor(es)', 'Tutor', 'Resumen', 'Palabras Clave', 'Error'];

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $filePath) {
            // Get relative path for cleaner representation in subfolders
            $relativeDir = trim(str_replace($dir, '', dirname($filePath)), DIRECTORY_SEPARATOR);
            $filename = basename($filePath);
            $displayName = $relativeDir ? $relativeDir.DIRECTORY_SEPARATOR.$filename : $filename;

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $summary['total']++;

            $title = null;
            $authors = null;
            $tutor = null;
            $abstract = null;
            $keywords = null;
            $errorMsg = '';

            try {
                $text = $extractor->extractText($filePath);

                $title = $extractor->extractTitle($text);
                $authors = $extractor->extractAuthors($text);
                $tutor = $extractor->extractTutor($text);
                $abstract = $extractor->extractAbstract($text);
                $keywords = $extractor->extractKeywords($text);

                if ($title) {
                    $summary['title_ok']++;
                }
                if ($authors) {
                    $summary['authors_ok']++;
                }
                if ($tutor) {
                    $summary['tutor_ok']++;
                }
                if ($abstract) {
                    $summary['abstract_ok']++;
                }
                if ($keywords) {
                    $summary['keywords_ok']++;
                }

            } catch (\Exception $e) {
                $summary['errors']++;
                $errorMsg = $e->getMessage();
            }

            $status = ($errorMsg === '') ? 'OK' : 'ERROR';
            $results[] = [
                'filename' => $displayName,
                'status' => $status,
                'title' => $title ? '✔' : '✘',
                'authors' => $authors ? '✔' : '✘',
                'tutor' => $tutor ? '✔' : '✘',
                'abstract' => $abstract ? '✔' : '✘',
                'keywords' => $keywords ? '✔' : '✘',
                'error' => $errorMsg ?: '-',

                // Raw values for PDF generation
                'title_raw' => $title,
                'authors_raw' => $authors,
                'tutor_raw' => $tutor,
                'abstract_raw' => $abstract,
                'keywords_raw' => $keywords,
            ];

            // Save to CSV rows
            $csvRows[] = [
                $displayName,
                $status,
                $title ?? '',
                $authors ?? '',
                $tutor ?? '',
                $abstract ?? '',
                $keywords ?? '',
                $errorMsg,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Map results for Console output to prevent raw fields pollution
        $consoleResults = array_map(function ($item) {
            return [
                $item['filename'],
                $item['status'],
                $item['title'],
                $item['authors'],
                $item['tutor'],
                $item['abstract'],
                $item['keywords'],
                $item['error'],
            ];
        }, $results);

        // Print Detail Table
        $this->info('Batch Extraction Detail:');
        $this->table(
            ['Archivo', 'Estado', 'Título', 'Autor(es)', 'Tutor', 'Resumen', 'P. Clave', 'Error'],
            $consoleResults
        );

        // Print Summary Table
        $total = $summary['total'];
        $this->info('Batch Summary (Success Rates):');
        $this->table(
            ['Métrica', 'Exitosos', 'Porcentaje'],
            [
                ['Título', $summary['title_ok']." / $total", number_format(($summary['title_ok'] / $total) * 100, 2).'%'],
                ['Autor(es)', $summary['authors_ok']." / $total", number_format(($summary['authors_ok'] / $total) * 100, 2).'%'],
                ['Tutor', $summary['tutor_ok']." / $total", number_format(($summary['tutor_ok'] / $total) * 100, 2).'%'],
                ['Resumen', $summary['abstract_ok']." / $total", number_format(($summary['abstract_ok'] / $total) * 100, 2).'%'],
                ['Palabras Clave', $summary['keywords_ok']." / $total", number_format(($summary['keywords_ok'] / $total) * 100, 2).'%'],
                ['Errores de Lectura', $summary['errors']." / $total", number_format(($summary['errors'] / $total) * 100, 2).'%'],
            ]
        );

        // Save CSV Report
        $reportPath = base_path('context/reporte_pruebas.csv');
        $fp = fopen($reportPath, 'w');
        // Add UTF-8 BOM for Excel Spanish encoding compatibility
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        foreach ($csvRows as $row) {
            fputcsv($fp, $row, ';');
        }
        fclose($fp);
        $this->info('CSV Report generated successfully at: '.$reportPath);

        // Save PDF Report
        $this->info('Generating PDF Report (this may take a moment due to the number of files)...');
        try {
            $pdf = DomPdf::loadView('reports.metadata_extraction', compact('results', 'summary'));
            $pdf->setPaper('letter', 'portrait');
            $pdfPath = base_path('context/reporte_tesis_ingenieria.pdf');
            $pdf->save($pdfPath);
            $this->info('PDF Report generated successfully at: '.$pdfPath);
        } catch (\Exception $e) {
            $this->error('Failed to generate PDF Report: '.$e->getMessage());
        }

        return 0;
    }
}
