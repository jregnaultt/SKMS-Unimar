<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicProgramRequest;
use App\Http\Requests\Admin\UpdateAcademicProgramRequest;
use App\Models\AcademicProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAcademicProgramController extends Controller
{
    /**
     * Display a listing of the academic programs.
     */
    public function index(): View
    {
        $programs = AcademicProgram::orderBy('name')->paginate(10);

        return view('admin.programs.index', compact('programs'));
    }

    /**
     * Show the form for creating a new academic program.
     */
    public function create(): View
    {
        return view('admin.programs.create');
    }

    /**
     * Store a newly created academic program in storage.
     */
    public function store(StoreAcademicProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        AcademicProgram::create($data);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa académico creado correctamente.');
    }

    /**
     * Show the form for editing the specified academic program.
     */
    public function edit(AcademicProgram $program): View
    {
        return view('admin.programs.edit', compact('program'));
    }

    /**
     * Update the specified academic program in storage.
     */
    public function update(UpdateAcademicProgramRequest $request, AcademicProgram $program): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $program->update($data);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa académico actualizado correctamente.');
    }

    /**
     * Remove the specified academic program from storage.
     */
    public function destroy(AcademicProgram $program): RedirectResponse
    {
        // Prevent deletion if associated with research lines or productions
        if ($program->researchLines()->exists()) {
            return back()->with('error', 'No se puede eliminar el programa porque tiene líneas de investigación asociadas.');
        }

        $program->delete();

        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa académico eliminado correctamente.');
    }
}
