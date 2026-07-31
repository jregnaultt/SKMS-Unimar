<?php

namespace Tests\Feature;

use App\Jobs\ExportGoogleDocToPdfJob;
use App\Jobs\ExtractMetadataJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Comment;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\User;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleDocIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicProgram $program;

    protected ResearchLine $line;

    protected ProductionType $type;

    protected AcademicPeriod $period;

    protected User $tutor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Estudiante']);
        Role::firstOrCreate(['name' => 'Coordinador']);
        Role::firstOrCreate(['name' => 'Tutor']);

        $this->tutor = User::factory()->create();
        $this->tutor->assignRole('Tutor');

        $this->program = AcademicProgram::create([
            'name' => 'Ingeniería de Sistemas',
            'code' => 'ING-SIS',
            'is_active' => true,
        ]);

        $this->line = ResearchLine::create([
            'academic_program_id' => $this->program->id,
            'name' => 'Inteligencia Artificial',
            'is_active' => true,
        ]);

        $this->type = ProductionType::create([
            'name' => 'Tesis de Grado',
            'description' => 'Trabajo especial de grado',
        ]);

        $this->period = AcademicPeriod::create([
            'name' => '2026-I',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);
    }

    public function test_production_store_validation_requires_file_or_google_doc(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        // Neither file_id nor google_drive_file_id provided
        $response = $this->actingAs($user)->post(route('productions.store'), [
            'title' => 'Tesis sin archivo',
            'abstract' => 'Resumen sin archivo',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'keywords' => 'keyword1',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'action' => 'draft',
        ]);

        $response->assertSessionHasErrors(['file_id', 'google_drive_file_id']);
    }

    public function test_store_production_with_google_doc_dispatches_export_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $response = $this->actingAs($user)->post(route('productions.store'), [
            'title' => 'Tesis con Google Docs',
            'abstract' => 'Resumen con Google Docs',
            'authors' => 'Autor de Prueba',
            'tutor_id' => $this->tutor->id,
            'keywords' => 'ia, tesis',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'google_drive_file_id' => 'google-file-123456',
            'google_document_title' => 'Mi Tesis de Grado',
            'google_access_token' => 'ya29.access-token-xyz',
            'action' => 'draft',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('productions', [
            'title' => 'Tesis con Google Docs',
            'google_drive_file_id' => 'google-file-123456',
            'google_document_title' => 'Mi Tesis de Grado',
            'workflow_state' => 'draft',
        ]);

        Queue::assertPushed(ExportGoogleDocToPdfJob::class, function ($job) {
            return $job->fileId === 'google-file-123456'
                && $job->accessToken === 'ya29.access-token-xyz';
        });
    }

    public function test_google_drive_service_exports_pdf_successfully(): void
    {
        Http::fake([
            'https://www.googleapis.com/*' => Http::response('FAKE_PDF_BINARY_CONTENT', 200),
        ]);

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis Mock',
            'abstract' => 'Abstract',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'draft',
            'google_drive_file_id' => 'google-file-id',
        ]);

        $service = app(GoogleDriveService::class);
        $result = $service->exportToPdf($production, 'google-file-id', 'test-token');

        $this->assertTrue($result);
        $this->assertTrue($production->hasMedia('documento'));

        $mediaPath = $production->getFirstMedia('documento')->getPath();
        $this->assertEquals('FAKE_PDF_BINARY_CONTENT', file_get_contents($mediaPath));
    }

    public function test_show_production_renders_google_docs_iframe(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'under_tutor_review',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        // Associate the student user as author so they are authorized to view it
        $production->users()->attach($user->id, ['role' => 'author']);

        $response = $this->actingAs($user)->get(route('productions.show', $production));

        $response->assertStatus(200);
        $response->assertSee('google-file-999999');
        $response->assertSee('Editar Documento');
        $response->assertSee('Sincronizar Cambios');
    }

    public function test_sync_google_doc_endpoint_refreshes_pdf_successfully(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/google-file-999999/export*' => Http::response('FAKE_PDF_BINARY_CONTENT_UPDATED', 200),
            'https://www.googleapis.com/drive/v3/files/google-file-999999/comments*' => Http::response([
                'comments' => [
                    [
                        'id' => 'google-comment-abc',
                        'content' => 'Comentario del tutor en Google Docs',
                        'author' => [
                            'displayName' => 'Oswald Marín',
                            'emailAddress' => 'omarin.4205@unimar.edu.ve',
                        ],
                        'resolved' => false,
                        'createdTime' => '2026-07-06T00:00:00.000Z',
                        'replies' => [
                            [
                                'id' => 'google-reply-xyz',
                                'content' => 'Respuesta del estudiante',
                                'author' => [
                                    'displayName' => 'Javier Regnault',
                                    'emailAddress' => 'jregnault.6759@unimar.edu.ve',
                                ],
                                'createdTime' => '2026-07-06T01:00:00.000Z',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['email' => 'jregnault.6759@unimar.edu.ve']);
        $user->assignRole('Estudiante');

        $tutor = User::factory()->create(['email' => 'omarin.4205@unimar.edu.ve']);
        $tutor->assignRole('Tutor');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'draft',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        $production->users()->attach($user->id, ['role' => 'author']);
        $production->users()->attach($tutor->id, ['role' => 'tutor']);

        $response = $this->actingAs($user)->postJson(route('productions.sync', $production), [
            'google_access_token' => 'new-access-token-999',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'document_url']);
        $this->assertEquals('¡Documento sincronizado con éxito!', $response->json('message'));

        $this->assertTrue($production->fresh()->hasMedia('documento'));
        $mediaPath = $production->fresh()->getFirstMedia('documento')->getPath();
        $this->assertEquals('FAKE_PDF_BINARY_CONTENT_UPDATED', file_get_contents($mediaPath));

        // Assert comments and replies were created in the database
        $this->assertDatabaseHas('comments', [
            'production_id' => $production->id,
            'google_comment_id' => 'google-comment-abc',
            'content' => 'Comentario del tutor en Google Docs',
            'user_id' => $tutor->id,
        ]);

        $this->assertDatabaseHas('comments', [
            'production_id' => $production->id,
            'google_reply_id' => 'google-reply-xyz',
            'content' => 'Respuesta del estudiante',
            'user_id' => $user->id,
        ]);
    }

    public function test_extract_google_metadata_endpoint_downloads_and_dispatches_job(): void
    {
        Queue::fake();
        Http::fake([
            'https://www.googleapis.com/*' => Http::response('FAKE_PDF_BINARY_CONTENT', 200),
        ]);

        $user = User::factory()->create();
        $user->assignRole('Estudiante');

        $response = $this->actingAs($user)->postJson(route('productions.extract-google'), [
            'google_drive_file_id' => 'google-file-123456',
            'google_access_token' => 'ya29.access-token-xyz',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'file_id']);
        $this->assertEquals('processing', $response->json('status'));

        $fileId = $response->json('file_id');

        Queue::assertPushed(ExtractMetadataJob::class, function ($job) use ($user, $fileId) {
            return $job->userId === $user->id
                && $job->fileId === $fileId
                && $job->deleteAfterExtraction === true;
        });
    }

    /**
     * Test that changing a comment status to addressed resolves the comment in Google Docs.
     */
    public function test_changing_comment_status_to_addressed_resolves_google_doc_comment(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/google-file-999999/comments/google-comment-abc/replies*' => Http::response(['id' => 'reply-123'], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'jregnault.6759@unimar.edu.ve',
            'google_refresh_token' => 'mock-refresh-token',
            'google_access_token' => 'mock-access-token',
            'google_token_expires_at' => now()->addHour(),
        ]);
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'under_review',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        $production->users()->attach($user->id, ['role' => 'author']);

        $comment = Comment::create([
            'production_id' => $production->id,
            'google_comment_id' => 'google-comment-abc',
            'content' => 'Comentario',
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->patch(route('comments.update-status', $comment), [
            'status' => 'addressed',
        ]);

        $response->assertRedirect();
        $this->assertEquals('addressed', $comment->fresh()->status->value);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://www.googleapis.com/drive/v3/files/google-file-999999/comments/google-comment-abc/replies')
                && $request['action'] === 'resolve';
        });
    }

    /**
     * Test that student replying to an observation posts the reply to Google Docs.
     */
    public function test_student_reply_to_observation_posts_to_google_docs(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/google-file-999999/comments/google-comment-abc/replies*' => Http::response(['id' => 'google-reply-abc123'], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'jregnault.6759@unimar.edu.ve',
            'google_refresh_token' => 'mock-refresh-token',
            'google_access_token' => 'mock-access-token',
            'google_token_expires_at' => now()->addHour(),
        ]);
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'under_review',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        $production->users()->attach($user->id, ['role' => 'author']);

        $comment = Comment::create([
            'production_id' => $production->id,
            'google_comment_id' => 'google-comment-abc',
            'content' => 'Comentario del tutor',
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->post(route('comments.reply', $comment), [
            'content' => 'Ya corregí este punto en el documento.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'parent_id' => $comment->id,
            'google_reply_id' => 'google-reply-abc123',
            'content' => 'Ya corregí este punto en el documento.',
            'user_id' => $user->id,
        ]);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://www.googleapis.com/drive/v3/files/google-file-999999/comments/google-comment-abc/replies')
                && $request['content'] === 'Ya corregí este punto en el documento.'
                && ! isset($request['action']);
        });
    }

    /**
     * Test that changing status to addressed fails and returns error on Google API failure.
     */
    public function test_resolve_google_doc_comment_throws_error_on_google_api_failure(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/google-file-999999/comments/google-comment-abc/replies*' => Http::response('Unauthorized', 401),
        ]);

        $user = User::factory()->create([
            'email' => 'jregnault.6759@unimar.edu.ve',
            'google_refresh_token' => 'mock-refresh-token',
            'google_access_token' => 'mock-access-token',
            'google_token_expires_at' => now()->addHour(),
        ]);
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'under_review',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        $production->users()->attach($user->id, ['role' => 'author']);

        $comment = Comment::create([
            'production_id' => $production->id,
            'google_comment_id' => 'google-comment-abc',
            'content' => 'Comentario',
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->patch(route('comments.update-status', $comment), [
            'status' => 'addressed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['status']);
        $this->assertEquals('in_progress', $comment->fresh()->status->value);
    }

    /**
     * Test that replying to a comment fails and returns error on Google API failure.
     */
    public function test_reply_to_google_doc_comment_throws_error_on_google_api_failure(): void
    {
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/google-file-999999/comments/google-comment-abc/replies*' => Http::response('Forbidden', 403),
        ]);

        $user = User::factory()->create([
            'email' => 'jregnault.6759@unimar.edu.ve',
            'google_refresh_token' => 'mock-refresh-token',
            'google_access_token' => 'mock-access-token',
            'google_token_expires_at' => now()->addHour(),
        ]);
        $user->assignRole('Estudiante');

        $production = Production::create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Tesis de prueba',
            'abstract' => 'Resumen',
            'authors' => 'Autor',
            'tutor' => 'Tutor',
            'academic_program_id' => $this->program->id,
            'research_line_id' => $this->line->id,
            'production_type_id' => $this->type->id,
            'academic_period_id' => $this->period->id,
            'workflow_state' => 'under_review',
            'google_drive_file_id' => 'google-file-999999',
        ]);

        $production->users()->attach($user->id, ['role' => 'author']);

        $comment = Comment::create([
            'production_id' => $production->id,
            'google_comment_id' => 'google-comment-abc',
            'content' => 'Comentario del tutor',
            'user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->post(route('comments.reply', $comment), [
            'content' => 'Intento de respuesta fallido.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['reply']);
        $this->assertDatabaseMissing('comments', [
            'parent_id' => $comment->id,
            'content' => 'Intento de respuesta fallido.',
        ]);
    }
}
