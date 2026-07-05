<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractMetadataJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Keyword;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BulkProductionController extends Controller
{
    /**
     * Show the bulk import main screen.
     */
    public function index(): View
    {
        $academicPrograms = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $productionTypes = ProductionType::orderBy('name')->get();
        $academicPeriods = AcademicPeriod::where('is_active', true)->orderBy('name', 'desc')->get();
        $researchLines = ResearchLine::where('is_active', true)->orderBy('name')->get();

        return view('admin.productions.import', compact(
            'academicPrograms',
            'productionTypes',
            'academicPeriods',
            'researchLines'
        ));
    }

    /**
     * Handle single file upload in the bulk batch.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:20480', // 20MB max
        ]);

        $file = $request->file('file');
        $fileId = Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();

        $path = $file->storeAs('temp_pdfs', $fileId.'.'.$extension, 'local');
        $fullPath = Storage::disk('local')->path($path);

        // Dispatch background extraction job
        ExtractMetadataJob::dispatch($request->user()->id, $fullPath, $fileId);

        return response()->json([
            'file_id' => $fileId,
            'filename' => $originalName,
            'status' => 'processing',
        ]);
    }

    /**
     * Bulk check status of active files.
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'required|string',
        ]);

        $fileIds = $request->input('file_ids');
        $results = [];

        foreach ($fileIds as $fileId) {
            $metadata = Cache::get("metadata_{$fileId}");
            if ($metadata) {
                $results[$fileId] = [
                    'status' => 'completed',
                    'metadata' => $metadata,
                ];
            } else {
                $results[$fileId] = [
                    'status' => 'processing',
                ];
            }
        }

        return response()->json($results);
    }

    /**
     * Store the batch of import items as published productions.
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'productions' => 'required|array|min:1',
            'productions.*.file_id' => 'required|string',
            'productions.*.title' => 'required|string|max:500',
            'productions.*.abstract' => 'required|string',
            'productions.*.authors' => 'required|string|max:500',
            'productions.*.tutor' => 'required|string|max:500',
            'productions.*.academic_program_id' => 'required|exists:academic_programs,id',
            'productions.*.research_line_id' => 'required|exists:research_lines,id',
            'productions.*.production_type_id' => 'required|exists:production_types,id',
            'productions.*.academic_period_id' => 'required|exists:academic_periods,id',
            'productions.*.keywords' => 'nullable|string',
        ]);

        $items = $request->input('productions');
        $importedCount = 0;

        try {
            DB::transaction(function () use ($items, &$importedCount) {
                foreach ($items as $item) {
                    $fileId = $item['file_id'];
                    $tempPathPdf = "temp_pdfs/{$fileId}.pdf";
                    $tempPathDocx = "temp_pdfs/{$fileId}.docx";
                    $tempFullPath = null;

                    if (Storage::disk('local')->exists($tempPathPdf)) {
                        $tempFullPath = Storage::disk('local')->path($tempPathPdf);
                    } elseif (Storage::disk('local')->exists($tempPathDocx)) {
                        $tempFullPath = Storage::disk('local')->path($tempPathDocx);
                    } else {
                        Log::error("Bulk import: Temporary file not found for file_id {$fileId}");

                        continue;
                    }

                    // Create the production directly in published state
                    $production = Production::create([
                        'uuid' => (string) Str::uuid(),
                        'title' => $item['title'],
                        'abstract' => $item['abstract'],
                        'authors' => $item['authors'],
                        'tutor' => $item['tutor'],
                        'academic_program_id' => $item['academic_program_id'],
                        'research_line_id' => $item['research_line_id'],
                        'production_type_id' => $item['production_type_id'],
                        'academic_period_id' => $item['academic_period_id'],
                        'workflow_state' => 'published',
                        'submission_date' => now(),
                        'approval_date' => now(),
                        'published_at' => now(),
                    ]);

                    // Sync keywords
                    if (! empty($item['keywords'])) {
                        $keywords = array_filter(
                            array_map('trim', explode(',', $item['keywords'])),
                            fn ($k) => strlen($k) > 0
                        );

                        $keywordIds = [];
                        foreach ($keywords as $kwName) {
                            $keyword = Keyword::firstOrCreate(['name' => $kwName]);
                            $keywordIds[] = $keyword->id;
                        }
                        $production->keywords()->sync($keywordIds);
                    }

                    // Associate the media document
                    $production->addMedia($tempFullPath)
                        ->toMediaCollection('documento');

                    // Clean cache and temporal files
                    Cache::forget("metadata_{$fileId}");
                    $importedCount++;
                }
            });

            return redirect()->route('dashboard')->with('success', "¡Se han importado con éxito {$importedCount} producciones científicas históricas!");
        } catch (\Exception $e) {
            Log::error('Error in bulk import storeBatch: '.$e->getMessage());

            return back()->withInput()->with('error', 'Ocurrió un error al procesar la importación masiva: '.$e->getMessage());
        }
    }
}
