<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Events\ProductionStateChanged;
use App\Http\Requests\StoreProductionRequest;
use App\Jobs\ExportGoogleDocToPdfJob;
use App\Jobs\ExtractMetadataJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Comment;
use App\Models\DocumentVersion;
use App\Models\Enrollment;
use App\Models\Keyword;
use App\Models\PeriodMilestone;
use App\Models\Production;
use App\Models\ProductionMilestone;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\Revision;
use App\Models\Subject;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\MetadataExtractorService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductionController extends Controller
{
    public function __construct(
        protected MetadataExtractorService $metadataExtractorService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole(['Coordinador', 'Super Admin', 'Decano'])) {
            $productions = Production::with(['academicProgram', 'academicPeriod', 'productionType', 'subject'])
                ->latest()
                ->get();
        } else {
            $productions = Production::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
                ->with(['academicProgram', 'academicPeriod', 'productionType', 'subject'])
                ->latest()
                ->get();
        }

        return view('pages.productions.index', compact('productions'));
    }

    public function create()
    {
        $user = auth()->user();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        $activePeriod = AcademicPeriod::where('is_active', true)->orderBy('end_date', 'desc')->first();
        $enrollment = null;

        $previousProduction = null;

        if (! $isCoordinator && $user->hasRole('Estudiante')) {
            if ($activePeriod) {
                $enrollment = Enrollment::where('academic_period_id', $activePeriod->id)
                    ->where('student_id', $user->id)
                    ->with(['subject', 'tutor'])
                    ->first();
            }
            if (! $enrollment) {
                return redirect()->route('dashboard')->with('error', 'No tienes una inscripción activa para el período académico actual. Por favor, comunícate con la coordinación.');
            }

            // Query previous production for continuity
            $currentSubjectCode = $enrollment->subject->code ?? null;
            $previousSubjectCode = null;

            if ($currentSubjectCode === 'TRI1106341') {
                $previousSubjectCode = 'SMI1004341';
            } elseif ($currentSubjectCode === 'TRI1206441') {
                $previousSubjectCode = 'TRI1106341';
            }

            if ($previousSubjectCode) {
                $previousProduction = Production::whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->where('role', 'author');
                })
                    ->whereHas('subject', function ($q) use ($previousSubjectCode) {
                        $q->where('code', $previousSubjectCode);
                    })
                    ->whereIn('workflow_state', ['approved', 'published'])
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        $academicPrograms = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $productionTypes = ProductionType::orderBy('name')->get();
        $academicPeriods = AcademicPeriod::where('is_active', true)->orderBy('name', 'desc')->get();
        $researchLines = ResearchLine::where('is_active', true)->orderBy('name')->get();
        $tutors = User::role('Tutor')->orderBy('name')->get();

        return view('pages.productions.create', compact('academicPrograms', 'productionTypes', 'academicPeriods', 'researchLines', 'tutors', 'enrollment', 'activePeriod', 'previousProduction'));
    }

    public function store(StoreProductionRequest $request): RedirectResponse
    {
        $googleDriveFileId = $request->input('google_drive_file_id');
        $googleDocumentTitle = $request->input('google_document_title');
        $googleAccessToken = $request->input('google_access_token');

        $tempFullPath = null;

        if (! $googleDriveFileId) {
            $fileId = $request->input('file_id');
            $tempPathPdf = "temp_pdfs/{$fileId}.pdf";
            $tempPathDocx = "temp_pdfs/{$fileId}.docx";

            $relativePath = '';
            if (Storage::disk('local')->exists($tempPathPdf)) {
                $relativePath = $tempPathPdf;
            } elseif (Storage::disk('local')->exists($tempPathDocx)) {
                $relativePath = $tempPathDocx;
            } else {
                return back()->withInput()->with('error', 'El archivo subido no se encuentra en el servidor o ha expirado. Por favor, sube el documento de nuevo.');
            }

            $tempFullPath = Storage::disk('local')->path($relativePath);
        }

        try {
            $subject = Subject::find($request->input('subject_id'));
            $preassignedJury1Id = null;
            $preassignedJury2Id = null;

            if ($subject && $subject->code === 'TRI1206441') {
                $previousProduction = Production::whereHas('users', function ($q) use ($request) {
                    $q->where('users.id', $request->user()->id)->where('role', 'author');
                })
                    ->whereHas('subject', function ($q) {
                        $q->where('code', 'TRI1106341');
                    })
                    ->whereIn('workflow_state', ['approved', 'published'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($previousProduction) {
                    $preassignedJury1Id = $previousProduction->preassigned_jury_1_id;
                    $preassignedJury2Id = $previousProduction->preassigned_jury_2_id;
                }
            }

            DB::transaction(function () use ($request, $tempFullPath, $googleDriveFileId, $googleDocumentTitle, $googleAccessToken, $preassignedJury1Id, $preassignedJury2Id) {
                $tutorUser = User::findOrFail($request->input('tutor_id'));

                // 1. Create the production record
                $production = Production::create([
                    'uuid' => (string) Str::uuid(),
                    'title' => $request->input('title'),
                    'abstract' => $request->input('abstract'),
                    'authors' => $request->input('authors'),
                    'tutor' => $tutorUser->name,
                    'academic_program_id' => $request->input('academic_program_id'),
                    'research_line_id' => $request->input('research_line_id'),
                    'production_type_id' => $request->input('production_type_id'),
                    'academic_period_id' => $request->input('academic_period_id'),
                    'subject_id' => $request->input('subject_id'),
                    'workflow_state' => 'draft',
                    'submission_date' => null,
                    'google_drive_file_id' => $googleDriveFileId,
                    'google_document_title' => $googleDocumentTitle,
                ]);

                // 2. Process keywords
                $keywords = array_filter(
                    array_map('trim', explode(',', $request->input('keywords'))),
                    fn ($k) => strlen($k) > 0
                );

                $keywordIds = [];
                foreach ($keywords as $kwName) {
                    $keyword = Keyword::firstOrCreate(['name' => $kwName]);
                    $keywordIds[] = $keyword->id;
                }
                $production->keywords()->sync($keywordIds);

                // 3. Move document or dispatch async export job
                if ($googleDriveFileId) {
                    // Dispatch job to export Google Doc to PDF immediately
                    ExportGoogleDocToPdfJob::dispatch($production, $googleDriveFileId, $googleAccessToken);
                } else {
                    // Move the temporary document to Spatie MediaLibrary
                    $production->addMedia($tempFullPath)
                        ->toMediaCollection('documento');
                }

                // 4. Associate current user as author (pivot)
                $production->users()->attach($request->user()->id, [
                    'role' => 'author',
                ]);

                // 5. Associate the selected tutor as tutor (pivot)
                $production->users()->attach($tutorUser->id, [
                    'role' => 'tutor',
                ]);

                // Attach preassigned juries if they exist
                if ($preassignedJury1Id) {
                    $production->users()->attach($preassignedJury1Id, ['role' => 'jury']);
                }
                if ($preassignedJury2Id) {
                    $production->users()->attach($preassignedJury2Id, ['role' => 'jury']);
                }

                // 6. Copy milestones from period_milestones to production_milestones
                if ($production->subject_id) {
                    $periodMilestones = PeriodMilestone::where('academic_period_id', $production->academic_period_id)
                        ->where('subject_id', $production->subject_id)
                        ->where(function ($query) use ($tutorUser) {
                            $query->whereNull('tutor_id')
                                ->orWhere('tutor_id', $tutorUser->id);
                        })
                        ->get();

                    foreach ($periodMilestones as $pm) {
                        if ($pm->student_id && $pm->student_id !== auth()->id()) {
                            continue;
                        }
                        if (is_array($pm->excluded_student_ids) && in_array(auth()->id(), $pm->excluded_student_ids)) {
                            continue;
                        }
                        ProductionMilestone::create([
                            'production_id' => $production->id,
                            'subject_id' => $production->subject_id,
                            'period_milestone_id' => $pm->id,
                            'type' => $pm->type,
                            'title' => $pm->title,
                            'scheduled_date' => $pm->scheduled_date,
                            'status' => 'pending',
                            'notify_tutor' => $pm->notify_tutor ?? true,
                            'notify_jury' => $pm->notify_jury ?? false,
                        ]);
                    }
                }
            });

            $statusMessage = '¡Producción científica guardada como borrador con éxito!';

            return redirect()->route('dashboard')->with('success', $statusMessage);
        } catch (\Exception $e) {
            Log::error('Error storing production: '.$e->getMessage());

            return back()->withInput()->with('error', 'Ocurrió un error al guardar la producción: '.$e->getMessage());
        }
    }

    public function extractMetadata(Request $request)
    {
        $fieldName = $request->hasFile('documento') ? 'documento' : ($request->hasFile('pdf') ? 'pdf' : 'documento');

        $request->validate([
            $fieldName => 'required|file|extensions:pdf,docx|max:5120', // 5MB max
        ]);

        $file = $request->file($fieldName);
        $fileId = Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs('temp_pdfs', $fileId.'.'.$extension, 'local');

        $fullPath = Storage::disk('local')->path($path);

        ExtractMetadataJob::dispatch($request->user()->id, $fullPath, $fileId);

        return response()->json([
            'status' => 'processing',
            'file_id' => $fileId,
        ]);
    }

    public function extractGoogleMetadata(Request $request)
    {
        $request->validate([
            'google_drive_file_id' => 'required|string',
            'google_access_token' => 'required|string',
        ]);

        $fileId = $request->input('google_drive_file_id');
        $accessToken = $request->input('google_access_token');

        $tempFileId = Str::uuid()->toString();
        $tempPath = "temp_pdfs/{$tempFileId}.pdf";

        try {
            $response = Http::withToken($accessToken)
                ->get("https://www.googleapis.com/drive/v3/files/{$fileId}/export", [
                    'mimeType' => 'application/pdf',
                ]);

            if (! $response->successful()) {
                Log::error('Falla al exportar Google Doc para extracción: '.$response->body());

                return response()->json(['error' => 'No se pudo descargar el documento de Google Docs.'], 500);
            }

            Storage::disk('local')->put($tempPath, $response->body());
            $fullPath = Storage::disk('local')->path($tempPath);

            // Pass true to deleteAfterExtraction so the temp PDF gets deleted once extraction finishes
            ExtractMetadataJob::dispatch($request->user()->id, $fullPath, $tempFileId, true);

            return response()->json([
                'status' => 'processing',
                'file_id' => $tempFileId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error extractGoogleMetadata: '.$e->getMessage());

            return response()->json(['error' => 'Falla al procesar el documento: '.$e->getMessage()], 500);
        }
    }

    public function show(Production $production)
    {
        if (in_array($production->workflow_state, ['approved', 'published'])) {
            $production->increment('views_count');
        }
        $production->load(['academicProgram', 'researchLine', 'productionType', 'academicPeriod', 'users', 'subject']);

        $user = auth()->user();
        $isAssociated = $production->users()->where('user_id', $user->id)->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        if ($production->workflow_state !== 'published' && ! $isAssociated && ! $isCoordinator) {
            abort(403, 'No tienes autorización para ver esta producción científica.');
        }

        if ($production->google_drive_file_id) {
            try {
                $driveService = resolve(GoogleDriveService::class);
                $driveService->syncCommentsForProduction($production);
            } catch (\Exception $e) {
                Log::warning('No se pudieron sincronizar los comentarios de Google Docs al cargar show: '.$e->getMessage());
            }
        }

        $revisions = Revision::where('production_id', $production->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        $versions = DocumentVersion::where('production_id', $production->id)
            ->orderBy('version_number', 'asc')
            ->get();

        $comments = Comment::where('production_id', $production->id)
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $tutors = collect();
        $juries = collect();
        $assignedTutor = null;
        $assignedJuries = collect();

        if ($isCoordinator) {
            $tutors = User::role('Tutor')->orderBy('name')->get();
            $juries = User::role('Jurado')->orderBy('name')->get();
            $assignedTutor = $production->users()->wherePivot('role', 'tutor')->first();
            $assignedJuries = $production->users()->wherePivot('role', 'jury')->get();
        }

        return view('pages.productions.show', compact(
            'production', 'revisions', 'versions', 'comments',
            'tutors', 'juries', 'assignedTutor', 'assignedJuries'
        ));
    }

    public function assignUsers(Request $request, Production $production)
    {
        $request->validate([
            'tutor_id' => 'nullable|exists:users,id',
            'jury_1_id' => 'nullable|exists:users,id|different:jury_2_id',
            'jury_2_id' => 'nullable|exists:users,id|different:jury_1_id',
            'preassigned_jury_1_id' => 'nullable|exists:users,id|different:preassigned_jury_2_id',
            'preassigned_jury_2_id' => 'nullable|exists:users,id|different:preassigned_jury_1_id',
        ]);

        $user = auth()->user();
        if (! $user->hasRole(['Coordinador', 'Super Admin', 'Decano'])) {
            abort(403, 'No tienes autorización para realizar asignaciones.');
        }

        DB::transaction(function () use ($request, $production) {
            // Remove existing tutor role for this production
            $production->users()->wherePivot('role', 'tutor')->detach();
            // Remove existing jury role for this production
            $production->users()->wherePivot('role', 'jury')->detach();

            // Attach new tutor if selected
            if ($request->filled('tutor_id')) {
                $production->users()->attach($request->input('tutor_id'), ['role' => 'tutor']);
            }

            // Attach new juries if selected (Trabajo II)
            if ($request->filled('jury_1_id')) {
                $production->users()->attach($request->input('jury_1_id'), ['role' => 'jury']);
            }
            if ($request->filled('jury_2_id')) {
                $production->users()->attach($request->input('jury_2_id'), ['role' => 'jury']);
            }

            // Save preassigned juries if it is Trabajo I
            if ($production->subject?->code === 'TRI1106341') {
                $production->update([
                    'preassigned_jury_1_id' => $request->input('preassigned_jury_1_id'),
                    'preassigned_jury_2_id' => $request->input('preassigned_jury_2_id'),
                ]);
            }
        });

        return back()->with('success', 'Asignación de tutor y jurados guardada con éxito.');
    }

    public function edit(Production $production)
    {
        $production->load(['academicProgram', 'researchLine', 'productionType', 'academicPeriod', 'users']);

        $user = auth()->user();
        $isAuthor = $production->users()->where('user_id', $user->id)->wherePivot('role', 'author')->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        if (! $isAuthor && ! $isCoordinator) {
            abort(403, 'No tienes autorización para editar esta producción científica.');
        }

        if (! $production->google_drive_file_id) {
            return redirect()->route('productions.show', $production)->with('error', 'Esta producción no está vinculada a Google Docs.');
        }

        return view('pages.productions.edit', compact('production'));
    }

    public function downloadDocument(Production $production)
    {
        $user = auth()->user();
        $isAssociated = $production->users()->where('user_id', $user->id)->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        if ($production->workflow_state !== 'published' && ! $isAssociated && ! $isCoordinator) {
            abort(403, 'No tienes autorización para acceder a este documento.');
        }

        if (in_array($production->workflow_state, ['approved', 'published'])) {
            $production->increment('downloads_count');
        }

        $media = $production->getFirstMedia('documento');
        if (! $media) {
            abort(404, 'Documento no encontrado.');
        }

        // Clean on-the-fly for retroactive support of existing files
        $this->metadataExtractorService->removeExtraUnimarCoverPage($media->getPath());

        $period = $production->academicPeriod?->name ?? 'Periodo';
        $authors = $production->authors ?? 'Autor';
        $filename = "{$period} - {$authors}.pdf";
        $cleanFilename = str_replace(['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>'], '-', $filename);

        if (request()->has('download')) {
            return response()->download($media->getPath(), $cleanFilename, [
                'Content-Type' => $media->mime_type ?? 'application/pdf',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        return response()->file($media->getPath(), [
            'Content-Disposition' => 'inline; filename="'.$cleanFilename.'"',
            'Content-Type' => $media->mime_type ?? 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function downloadVersionDocument(DocumentVersion $version)
    {
        $production = $version->production;
        $user = auth()->user();
        $isAssociated = $production->users()->where('user_id', $user->id)->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        if (! $isAssociated && ! $isCoordinator) {
            abort(403, 'No tienes autorización para acceder a este documento.');
        }

        $media = $version->getFirstMedia('documento_version');
        if (! $media) {
            abort(404, 'Documento de versión no encontrado.');
        }

        // Clean on-the-fly for retroactive support of existing files
        $this->metadataExtractorService->removeExtraUnimarCoverPage($media->getPath());

        $period = $production->academicPeriod?->name ?? 'Periodo';
        $authors = $production->authors ?? 'Autor';
        $filename = "{$period} - {$authors} - V{$version->version_number}.pdf";
        $cleanFilename = str_replace(['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>'], '-', $filename);

        if (request()->has('download')) {
            return response()->download($media->getPath(), $cleanFilename, [
                'Content-Type' => $media->mime_type ?? 'application/pdf',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        return response()->file($media->getPath(), [
            'Content-Disposition' => 'inline; filename="'.$cleanFilename.'"',
            'Content-Type' => $media->mime_type ?? 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function submitDraft(Request $request, Production $production): RedirectResponse
    {
        $isAuthor = $production->users()
            ->where('user_id', $request->user()->id)
            ->wherePivot('role', 'author')
            ->exists();

        if (! $isAuthor) {
            abort(403, 'No tienes autorización sobre esta producción científica.');
        }

        if ($production->workflow_state !== 'draft') {
            return back()->with('error', 'Solo los borradores pueden ser enviados a revisión.');
        }

        $production->update([
            'workflow_state' => 'under_tutor_review',
            'submission_date' => now(),
        ]);

        event(new ProductionStateChanged($production, 'draft', 'under_tutor_review', $request->user()));

        return back()->with('success', '¡El borrador ha sido enviado a revisión con éxito!');
    }

    public function destroy(Request $request, Production $production): RedirectResponse
    {
        $isAuthor = $production->users()
            ->where('user_id', $request->user()->id)
            ->wherePivot('role', 'author')
            ->exists();

        if (! $isAuthor) {
            abort(403, 'No tienes autorización sobre esta producción científica.');
        }

        if ($production->workflow_state !== 'draft') {
            return back()->with('error', 'Solo los borradores pueden ser eliminados.');
        }

        $production->delete();

        return back()->with('success', 'El borrador ha sido eliminado con éxito.');
    }

    public function syncGoogleDoc(Request $request, Production $production)
    {
        $request->validate([
            'google_access_token' => 'required|string',
        ]);

        $isAuthor = $production->users()
            ->where('user_id', $request->user()->id)
            ->wherePivot('role', 'author')
            ->exists();
        $isCoordinator = $request->user()->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        if (! $isAuthor && ! $isCoordinator) {
            return response()->json(['error' => 'No tienes autorización para sincronizar este documento.'], 403);
        }

        if (! $production->google_drive_file_id) {
            return response()->json(['error' => 'Esta producción no está vinculada a Google Docs.'], 400);
        }

        try {
            // Delete existing media first if we are replacing it
            $production->clearMediaCollection('documento');

            $driveService = app(GoogleDriveService::class);
            $driveService->exportToPdf($production, $production->google_drive_file_id, $request->input('google_access_token'));
            $driveService->syncComments($production, $production->google_drive_file_id, $request->input('google_access_token'));

            return response()->json([
                'status' => 'success',
                'message' => '¡Documento sincronizado con éxito!',
                'document_url' => route('productions.document', $production).'?t='.time(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncGoogleDoc: '.$e->getMessage());

            return response()->json(['error' => 'Falla al exportar el documento: '.$e->getMessage()], 500);
        }
    }

    public function requestJuryReview(Request $request, Production $production): RedirectResponse
    {
        $isAuthor = $production->users()
            ->where('user_id', $request->user()->id)
            ->wherePivot('role', 'author')
            ->exists();

        if (! $isAuthor) {
            abort(403, 'No tienes autorización sobre esta producción científica.');
        }

        if ($production->workflow_state !== 'under_tutor_review') {
            return back()->with('error', 'Solo se puede solicitar revisión de jurado cuando el documento está en revisión del tutor.');
        }

        $hasPendingComments = $production->comments()
            ->whereIn('status', [CommentStatus::Pending->value, CommentStatus::InProgress->value])
            ->exists();

        if ($hasPendingComments) {
            return back()->with('error', 'Debes esperar a que tu tutor valide las correcciones. Tienes observaciones pendientes.');
        }

        $production->update([
            'jury_review_requested' => true,
        ]);

        // Find tutor to notify
        $tutor = $production->users()->wherePivot('role', 'tutor')->first();
        if ($tutor) {
            event(new ProductionStateChanged($production, 'under_tutor_review', 'under_tutor_review', $request->user(), 'Solicitud de revisión por jurado enviada al tutor.'));
        }

        return back()->with('success', '¡Se ha enviado la solicitud de revisión al jurado a tu tutor exitosamente!');
    }

    public function updateMetadata(Request $request, Production $production): RedirectResponse
    {
        $user = $request->user();
        $isAuthor = $production->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'author')
            ->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);

        if (! $isAuthor && ! $isCoordinator) {
            abort(403, 'No tienes autorización para editar esta producción científica.');
        }

        $request->validate([
            'abstract' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $production) {
            $production->update([
                'abstract' => $request->input('abstract'),
            ]);

            // Process keywords
            $keywords = array_filter(
                array_map('trim', explode(',', $request->input('keywords') ?? '')),
                fn ($k) => strlen($k) > 0
            );

            $keywordIds = [];
            foreach ($keywords as $kwName) {
                $keyword = Keyword::firstOrCreate(['name' => $kwName]);
                $keywordIds[] = $keyword->id;
            }
            $production->keywords()->sync($keywordIds);
        });

        return back()->with('success', '¡El resumen y las palabras clave se han actualizado correctamente!');
    }

    /**
     * Appeal a rejected production, moving it back to draft and incrementing appeals_count.
     */
    public function appeal(Request $request, Production $production): RedirectResponse
    {
        $workflowService = resolve(WorkflowService::class);
        $user = $request->user();

        try {
            $workflowService->transition($production, 'draft', $user, [
                'comment' => 'Apelación de rechazo iniciada por el estudiante.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'No puedes apelar este trabajo: '.$e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al procesar la apelación.');
        }

        return back()->with('success', 'Tu apelación ha sido enviada con éxito. El trabajo ha vuelto al estado de Borrador.');
    }
}
