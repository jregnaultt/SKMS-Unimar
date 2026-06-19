<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicPeriodRequest;
use App\Http\Requests\Admin\UpdateAcademicPeriodRequest;
use App\Models\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAcademicPeriodController extends Controller
{
    /**
     * Display a listing of the academic periods.
     */
    public function index(): View
    {
        $periods = AcademicPeriod::orderBy('start_date', 'desc')->paginate(10);

        return view('admin.periods.index', compact('periods'));
    }

    /**
     * Show the form for creating a new academic period.
     */
    public function create(): View
    {
        return view('admin.periods.create');
    }

    /**
     * Store a newly created academic period in storage.
     */
    public function store(StoreAcademicPeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        AcademicPeriod::create($data);

        return redirect()->route('admin.periods.index')
            ->with('success', 'Período académico creado correctamente.');
    }

    /**
     * Show the form for editing the specified academic period.
     */
    public function edit(AcademicPeriod $period): View
    {
        return view('admin.periods.edit', compact('period'));
    }

    /**
     * Update the specified academic period in storage.
     */
    public function update(UpdateAcademicPeriodRequest $request, AcademicPeriod $period): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $period->update($data);

        return redirect()->route('admin.periods.index')
            ->with('success', 'Período académico actualizado correctamente.');
    }

    /**
     * Remove the specified academic period from storage.
     */
    public function destroy(AcademicPeriod $period): RedirectResponse
    {
        // Block deletion if associated with productions
        $hasProductions = \DB::table('productions')
            ->where('periodo_academico_id', $period->id)
            ->exists();

        if ($hasProductions) {
            return back()->with('error', 'No se puede eliminar el período académico porque tiene producciones científicas asociadas.');
        }

        $period->delete();

        return redirect()->route('admin.periods.index')
            ->with('success', 'Período académico eliminado correctamente.');
    }
}
