<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionRequest;
use App\Jobs\ExtractMetadataJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\DocumentVersion;
use App\Models\Keyword;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use App\Models\Revision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductionController extends Controller
{
    public function create()
    {
        $academicPrograms = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $productionTypes = ProductionType::orderBy('name')->get();
        $academicPeriods = AcademicPeriod::where('is_active', true)->orderBy('name', 'desc')->get();
        $researchLines = ResearchLine::where('is_active', true)->orderBy('name')->get();

        return view('pages.productions.create', compact('academicPrograms', 'productionTypes', 'academicPeriods', 'researchLines'));
    }

    public function store(StoreProductionRequest $request): RedirectResponse
    {
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

        try {
            DB::transaction(function () use ($request, $tempFullPath) {
                // 1. Create the production record
                $production = Production::create([
                    'uuid' => (string) Str::uuid(),
                    'title' => $request->input('title'),
                    'abstract' => $request->input('abstract'),
                    'authors' => $request->input('authors'),
                    'tutor' => $request->input('tutor'),
                    'academic_program_id' => $request->input('academic_program_id'),
                    'research_line_id' => $request->input('research_line_id'),
                    'production_type_id' => $request->input('production_type_id'),
                    'academic_period_id' => $request->input('academic_period_id'),
                    'workflow_state' => $request->input('action') === 'submit' ? 'under_review' : 'draft',
                    'submission_date' => $request->input('action') === 'submit' ? now() : null,
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

                // 3. Move the temporary document to Spatie MediaLibrary
                $production->addMedia($tempFullPath)
                    ->toMediaCollection('documento');

                // 4. Associate current user as author (pivot)
                $production->users()->attach($request->user()->id, [
                    'role' => 'author',
                ]);
            });

            $statusMessage = $request->input('action') === 'submit'
                ? '¡Producción científica guardada y enviada a revisión con éxito!'
                : '¡Producción científica guardada como borrador con éxito!';

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
            $fieldName => 'required|file|extensions:pdf,docx|max:10240', // 10MB max
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

    public function show(Production $production)
    {
        $production->load(['academicProgram', 'researchLine', 'productionType', 'academicPeriod', 'users']);

        $user = auth()->user();
        $isAssociated = $production->users()->where('user_id', $user->id)->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin']);

        if ($production->workflow_state !== 'published' && ! $isAssociated && ! $isCoordinator) {
            abort(403, 'No tienes autorización para ver esta producción científica.');
        }

        $revisions = Revision::where('production_id', $production->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        $versions = DocumentVersion::where('production_id', $production->id)
            ->orderBy('version_number', 'asc')
            ->get();

        return view('pages.productions.show', compact('production', 'revisions', 'versions'));
    }

    public function downloadDocument(Production $production)
    {
        $user = auth()->user();
        $isAssociated = $production->users()->where('user_id', $user->id)->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin']);

        if ($production->workflow_state !== 'published' && ! $isAssociated && ! $isCoordinator) {
            abort(403, 'No tienes autorización para acceder a este documento.');
        }

        $media = $production->getFirstMedia('documento');
        if (! $media) {
            abort(404, 'Documento no encontrado.');
        }

        return response()->file($media->getPath());
    }

    public function downloadVersionDocument(DocumentVersion $version)
    {
        $production = $version->production;
        $user = auth()->user();
        $isAssociated = $production->users()->where('user_id', $user->id)->exists();
        $isCoordinator = $user->hasRole(['Coordinador', 'Super Admin']);

        if ($production->workflow_state !== 'published' && ! $isAssociated && ! $isCoordinator) {
            abort(403, 'No tienes autorización para acceder a este documento.');
        }

        $media = $version->getFirstMedia('documento_version');
        if (! $media) {
            abort(404, 'Documento de versión no encontrado.');
        }

        return response()->file($media->getPath());
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
            'workflow_state' => 'under_review',
            'submission_date' => now(),
        ]);

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
}
