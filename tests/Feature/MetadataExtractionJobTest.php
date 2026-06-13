<?php

namespace Tests\Feature;

use App\Events\MetadataExtracted;
use App\Jobs\ExtractMetadataJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MetadataExtractionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_event_with_extracted_metadata()
    {
        Event::fake();

        $user = User::factory()->create();
        $pdfPath = base_path('context/tesispruebas/TI - MARTINA MARIANA LODEIRO CALCAÑO.pdf');

        $job = new ExtractMetadataJob($user->id, $pdfPath, 'dummy-file-id');
        dispatch_sync($job);

        Event::assertDispatched(MetadataExtracted::class, function ($event) use ($user) {
            return $event->userId === $user->id
                && $event->fileId === 'dummy-file-id';
        });
    }
}
