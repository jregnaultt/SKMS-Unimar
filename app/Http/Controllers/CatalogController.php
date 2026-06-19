<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ResearchLine;
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
            ->with(['academicProgram', 'researchLine', 'productionType']);

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

        $productions = $query->orderBy('published_at', 'desc')->paginate(10);
        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $lines = ResearchLine::where('is_active', true)->orderBy('name')->get();

        return view('catalog.index', compact('productions', 'programs', 'lines'));
    }
}
