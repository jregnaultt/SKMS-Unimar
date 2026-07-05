<?php

namespace App\Http\Controllers;

use App\Models\ProductionMilestone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignedProductionController extends Controller
{
    /**
     * Display the list of assigned productions for evaluators or global overview for coordinators.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = auth()->user();

        // Obtener roles de Spatie para el layout
        $roles = $user->getRoleNames()->toArray();
        if (empty($roles)) {
            $roles = ['Estudiante'];
        }
        $activeRole = session('active_dashboard_role') ?? $roles[0];

        // 1. Vista para directivos (Coordinador y Decano)
        if ($user->hasAnyRole(['Coordinador', 'Decano'])) {
            $tutors = User::role('Tutor')
                ->with(['productions' => function ($query) {
                    $query->wherePivot('role', 'tutor')
                        ->with(['academicProgram', 'academicPeriod', 'users']);
                }])
                ->orderBy('name')
                ->get();

            $jurados = User::role('Jurado')
                ->with(['productions' => function ($query) {
                    $query->wherePivot('role', 'jury')
                        ->with(['academicProgram', 'academicPeriod', 'users']);
                }])
                ->orderBy('name')
                ->get();

            return view('assigned-productions.index', [
                'roles' => $roles,
                'activeRole' => $activeRole,
                'tutors' => $tutors,
                'jurados' => $jurados,
                'isDirectivo' => true,
            ]);
        }

        // 2. Vista para docentes (Tutor / Jurado)
        $tutorProductions = $user->productions()
            ->wherePivot('role', 'tutor')
            ->with(['academicProgram', 'academicPeriod', 'users'])
            ->latest()
            ->get();

        $juryProductions = $user->productions()
            ->wherePivot('role', 'jury')
            ->with(['academicProgram', 'academicPeriod', 'users'])
            ->latest()
            ->get();

        // Obtener IDs de todas las producciones del docente
        $productionIds = $tutorProductions->pluck('id')
            ->merge($juryProductions->pluck('id'))
            ->unique()
            ->toArray();

        // Obtener defensas programadas para estos trabajos
        $defensas = ProductionMilestone::whereIn('production_id', $productionIds)
            ->whereIn('type', ['defense', 'pre_defense'])
            ->with('production')
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return view('assigned-productions.index', [
            'roles' => $roles,
            'activeRole' => $activeRole,
            'tutorProductions' => $tutorProductions,
            'juryProductions' => $juryProductions,
            'defensas' => $defensas,
            'isDirectivo' => false,
        ]);
    }
}
