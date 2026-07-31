<?php

namespace Tests\Feature;

use App\Events\MetadataExtracted;
use App\Jobs\ExtractMetadataJob;
use App\Models\User;
use App\Services\MetadataExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\PdfToText\Pdf;
use Tests\TestCase;

class MetadataExtractionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_event_with_extracted_metadata()
    {
        Event::fake();

        // Mock the service so we don't rely on local files in CI
        $this->mock(MetadataExtractorService::class, function ($mock) {
            $mock->shouldReceive('removeExtraUnimarCoverPage')
                ->once()
                ->withAnyArgs();

            $mock->shouldReceive('extractMetadata')
                ->once()
                ->withAnyArgs()
                ->andReturn([
                    'title' => 'MÓDULO DE PRUEBA',
                    'authors' => 'Autor de Prueba',
                    'tutor' => 'Tutor de Prueba',
                    'abstract' => 'El resumen de la investigación...',
                    'keywords' => 'clave1, clave2',
                ]);
        });

        $user = User::factory()->create();

        $job = new ExtractMetadataJob($user->id, 'dummy-path.pdf', 'dummy-file-id');
        dispatch_sync($job);

        Event::assertDispatched(MetadataExtracted::class, function ($event) use ($user) {
            $metadata = isset($event->metadata['metadata']) ? $event->metadata['metadata'] : $event->metadata;

            return $event->userId === $user->id
                && $event->fileId === 'dummy-file-id'
                && $metadata['title'] === 'MÓDULO DE PRUEBA'
                && $metadata['authors'] === 'Autor de Prueba';
        });
    }

    public function test_it_removes_unimar_blank_cover_page()
    {
        if (empty(shell_exec('which pdftotext'))) {
            $this->markTestSkipped('pdftotext binary not found on this host.');
        }

        $html = '<html><head><style>.page-break { page-break-after: always; }</style></head><body>'
            .'<div>UNIVERSIDAD DE MARGARITA</div>'
            .'<div class="page-break"></div>'
            .'<div>DESARROLLO DE UNA INVESTIGACIÓN</div>'
            .'</body></html>';

        $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_test_').'.pdf';
        file_put_contents($tempFile, $pdfContent);

        $parser = new Pdf;
        $initialPage1 = $parser->setPdf($tempFile)->setOptions(['f 1', 'l 1'])->text();
        $this->assertStringContainsString('UNIVERSIDAD DE MARGARITA', $initialPage1);

        $service = new MetadataExtractorService;
        $service->removeExtraUnimarCoverPage($tempFile);

        $cleanedPage1 = $parser->setPdf($tempFile)->setOptions(['f 1', 'l 1'])->text();
        $this->assertStringContainsString('DESARROLLO DE UNA INVESTIGACIÓN', $cleanedPage1);
        $this->assertStringNotContainsString('UNIVERSIDAD DE MARGARITA', $cleanedPage1);

        @unlink($tempFile);
    }
}
