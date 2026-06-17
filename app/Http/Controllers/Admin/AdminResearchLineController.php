<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResearchLineRequest;
use App\Http\Requests\Admin\UpdateResearchLineRequest;
use App\Models\AcademicProgram;
use App\Models\ResearchLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminResearchLineController extends Controller
{
    /**
     * Display a listing of the research lines.
     */
    public function index(): View
    {
        $lines = ResearchLine::with('academicProgram')->orderBy('name')->paginate(10);

        return view('admin.lines.index', compact('lines'));
    }

    /**
     * Show the form for creating a new research line.
     */
    public function create(): View
    {
        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();

        return view('admin.lines.create', compact('programs'));
    }

    /**
     * Store a newly created research line in storage.
     */
    public function store(StoreResearchLineRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        ResearchLine::create($data);

        return redirect()->route('admin.lines.index')
            ->with('success', 'Línea de investigación creada correctamente.');
    }

    /**
     * Show the form for editing the specified research line.
     */
    public function edit(ResearchLine $line): View
    {
        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();

        return view('admin.lines.edit', compact('line', 'programs'));
    }

    /**
     * Update the specified research line in storage.
     */
    public function update(UpdateResearchLineRequest $request, ResearchLine $line): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $line->update($data);

        return redirect()->route('admin.lines.index')
            ->with('success', 'Línea de investigación actualizada correctamente.');
    }

    /**
     * Remove the specified research line from storage.
     */
    public function destroy(ResearchLine $line): RedirectResponse
    {
        // Block deletion if associated with productions
        // Wait, does ResearchLine model have a relation or do we check via DB or relation?
        // Let's check if the relation exists or use DB count
        $hasProductions = \DB::table('productions')
            ->where('linea_investigacion_id', $line->id)
            ->exists();

        if ($hasProductions) {
            return back()->with('error', 'No se puede eliminar la línea de investigación porque tiene producciones científicas asociadas.');
        }

        $line->delete();

        return redirect()->route('admin.lines.index')
            ->with('success', 'Línea de investigación eliminada correctamente.');
    }
}
