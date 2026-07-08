<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Display a listing of published scientific productions with search and filters.
     */
    public function index(Request $request): View
    {
        $query = Production::query()
            ->published()
            ->with(['academicProgram', 'researchLine', 'productionType', 'keywords', 'media']);

        // Full-text search
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abstract', 'like', "%{$search}%")
                    ->orWhere('authors', 'like', "%{$search}%")
                    ->orWhereHas('keywords', function ($kq) use ($search) {
                        $kq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sidebar filters
        if ($request->filled('program')) {
            $query->where('academic_program_id', $request->input('program'));
        }
        if ($request->filled('line')) {
            $query->where('research_line_id', $request->input('line'));
        }
        if ($request->filled('year')) {
            $query->whereYear('published_at', $request->input('year'));
        }
        if ($request->filled('type')) {
            $query->where('production_type_id', $request->input('type'));
        }
        if ($request->filled('tutor')) {
            $query->where('tutor', $request->input('tutor'));
        }

        $productions = $query->orderBy('published_at', 'desc')->paginate(10);

        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $lines = ResearchLine::where('is_active', true)->orderBy('name')->get();
        $productionTypes = ProductionType::orderBy('name')->get();

        // Get distinct tutor names from published productions to populate filter
        $tutors = Production::published()
            ->whereNotNull('tutor')
            ->where('tutor', '!=', '')
            ->distinct()
            ->orderBy('tutor')
            ->pluck('tutor');

        if (auth()->check()) {
            return view('catalog.index', compact('productions', 'programs', 'lines', 'productionTypes', 'tutors'));
        }

        return view('catalog.public-index', compact('productions', 'programs', 'lines', 'productionTypes', 'tutors'));
    }

    /**
     * Search scientific productions via HTTP QUERY or POST as fallback.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => 'nullable|string|max:255',
            'programa_id' => 'nullable|exists:academic_programs,id',
            'linea_investigacion_id' => 'nullable|exists:research_lines,id',
            'tipo_produccion_id' => 'nullable|exists:production_types,id',
            'tutor' => 'nullable|string|max:255',
            'anio' => 'nullable|integer',
        ]);

        $query = Production::query()
            ->published()
            ->with(['academicProgram', 'researchLine', 'productionType', 'keywords', 'media']);

        if (! empty($validated['titulo'])) {
            $query->where('title', 'like', "%{$validated['titulo']}%");
        }

        if (! empty($validated['programa_id'])) {
            $query->where('academic_program_id', $validated['programa_id']);
        }

        if (! empty($validated['linea_investigacion_id'])) {
            $query->where('research_line_id', $validated['linea_investigacion_id']);
        }

        if (! empty($validated['tipo_produccion_id'])) {
            $query->where('production_type_id', $validated['tipo_produccion_id']);
        }

        if (! empty($validated['tutor'])) {
            $query->where('tutor', $validated['tutor']);
        }

        if (! empty($validated['anio'])) {
            $query->whereYear('published_at', $validated['anio']);
        }

        $productions = $query->orderBy('published_at', 'desc')->paginate(10);

        return response()->json($productions);
    }

    /**
     * Display the public detail page of a published scientific production.
     */
    public function showPublic(string $uuid): View
    {
        $production = Production::where('uuid', $uuid)
            ->published()
            ->with(['academicProgram', 'researchLine', 'productionType', 'keywords'])
            ->firstOrFail();

        return view('catalog.show', compact('production'));
    }

    /**
     * Download the PDF of a published scientific production publicly.
     */
    public function downloadPublicPdf(string $uuid)
    {
        $production = Production::where('uuid', $uuid)
            ->published()
            ->firstOrFail();

        $media = $production->getFirstMedia('documento');

        if (! $media) {
            abort(404, 'Archivo no encontrado.');
        }

        $period = $production->academicPeriod?->name ?? 'Periodo';
        $authors = $production->authors ?? 'Autor';
        $filename = "{$period} - {$authors}.pdf";
        $cleanFilename = str_replace(['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>'], '-', $filename);

        return response()->download($media->getPath(), $cleanFilename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
