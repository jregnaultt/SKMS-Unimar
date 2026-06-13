<?php

namespace Tests\Feature;

use App\Events\MetadataExtracted;
use App\Jobs\ExtractMetadataJob;
use App\Models\User;
use App\Services\MetadataExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MetadataExtractionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_event_with_extracted_metadata()
    {
        Event::fake();

        // Mock the service so we don't rely on local files in CI
        $this->mock(MetadataExtractorService::class, function ($mock) {
            $mock->shouldReceive('extractMetadata')
                ->once()
                ->with('dummy-path.pdf')
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
            return $event->userId === $user->id
                && $event->fileId === 'dummy-file-id'
                && $event->metadata['title'] === 'MÓDULO DE PRUEBA'
                && $event->metadata['authors'] === 'Autor de Prueba';
        });
    }
}
