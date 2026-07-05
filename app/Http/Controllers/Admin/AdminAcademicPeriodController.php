<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicPeriodRequest;
use App\Http\Requests\Admin\UpdateAcademicPeriodRequest;
use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\PeriodMilestone;
use App\Models\Subject;
use App\Models\SubjectTutorPeriod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function edit(AcademicPeriod $period, Request $request): View
    {
        $tab = $request->query('tab', 'info');

        $subjects = collect();
        $tutors = collect();
        $students = collect();
        $activeTutors = collect();
        $enrollments = collect();
        $subjectTutors = collect();
        $milestones = collect();

        if ($tab === 'tutors') {
            $subjects = Subject::orderBy('name')->get();
            $tutors = User::role('Tutor')->orderBy('name')->get();
            $activeTutors = SubjectTutorPeriod::where('academic_period_id', $period->id)
                ->with(['subject', 'tutor'])
                ->get();
        } elseif ($tab === 'enrollments') {
            $subjects = Subject::orderBy('name')->get();
            $students = User::role('Estudiante')->orderBy('name')->get();
            $enrollments = Enrollment::where('academic_period_id', $period->id)
                ->with(['student', 'subject', 'tutor'])
                ->get();
            $subjectTutors = SubjectTutorPeriod::where('academic_period_id', $period->id)
                ->with('tutor')
                ->get()
                ->groupBy('subject_id');
        } elseif ($tab === 'milestones') {
            $subjects = Subject::orderBy('name')->get();
            $tutors = User::role('Tutor')->orderBy('name')->get();
            $milestones = PeriodMilestone::where('academic_period_id', $period->id)
                ->with(['subject', 'tutor'])
                ->get();
        }

        return view('admin.periods.edit', compact(
            'period', 'tab', 'subjects', 'tutors', 'students',
            'activeTutors', 'enrollments', 'subjectTutors', 'milestones'
        ));
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

    public function storeTutor(Request $request, AcademicPeriod $period): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'tutor_id' => 'required|exists:users,id',
        ]);

        $tutor = User::findOrFail($request->input('tutor_id'));
        if (! $tutor->hasRole('Tutor')) {
            return back()->with('error', 'El usuario seleccionado no tiene el rol de Tutor.');
        }

        SubjectTutorPeriod::firstOrCreate([
            'academic_period_id' => $period->id,
            'subject_id' => $request->input('subject_id'),
            'tutor_id' => $request->input('tutor_id'),
        ]);

        return redirect()->route('admin.periods.edit', [$period, 'tab' => 'tutors'])
            ->with('success', 'Tutor asociado a la materia con éxito para este período.');
    }

    public function destroyTutor(AcademicPeriod $period, $id): RedirectResponse
    {
        $association = SubjectTutorPeriod::where('academic_period_id', $period->id)->findOrFail($id);
        $association->delete();

        return redirect()->route('admin.periods.edit', [$period, 'tab' => 'tutors'])
            ->with('success', 'Tutor desasociado de la materia con éxito.');
    }

    public function storeEnrollment(Request $request, AcademicPeriod $period): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'student_id' => 'required|exists:users,id',
            'tutor_id' => 'nullable|exists:users,id',
        ]);

        $student = User::findOrFail($request->input('student_id'));
        if (! $student->hasRole('Estudiante')) {
            return back()->with('error', 'El usuario seleccionado no tiene el rol de Estudiante.');
        }

        if ($request->filled('tutor_id')) {
            $tutor = User::findOrFail($request->input('tutor_id'));
            if (! $tutor->hasRole('Tutor')) {
                return back()->with('error', 'El tutor seleccionado no tiene el rol de Tutor.');
            }

            // Verify tutor is active in this subject/period
            $isActive = SubjectTutorPeriod::where('academic_period_id', $period->id)
                ->where('subject_id', $request->input('subject_id'))
                ->where('tutor_id', $tutor->id)
                ->exists();

            if (! $isActive) {
                return back()->with('error', 'El tutor seleccionado no está asignado a esta materia para este período.');
            }
        }

        Enrollment::updateOrCreate(
            [
                'academic_period_id' => $period->id,
                'student_id' => $request->input('student_id'),
            ],
            [
                'subject_id' => $request->input('subject_id'),
                'tutor_id' => $request->input('tutor_id'),
            ]
        );

        return redirect()->route('admin.periods.edit', [$period, 'tab' => 'enrollments'])
            ->with('success', 'Estudiante inscrito y asignado con éxito.');
    }

    public function destroyEnrollment(AcademicPeriod $period, Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->academic_period_id !== $period->id) {
            abort(403);
        }
        $enrollment->delete();

        return redirect()->route('admin.periods.edit', [$period, 'tab' => 'enrollments'])
            ->with('success', 'Inscripción eliminada con éxito.');
    }

    public function storeMilestone(Request $request, AcademicPeriod $period): RedirectResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'tutor_id' => 'nullable|exists:users,id',
            'student_id' => 'nullable|exists:users,id',
            'type' => 'required|in:delivery,defense,pre_defense,system_defense',
            'title' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'notify_tutor' => 'nullable|boolean',
            'notify_jury' => 'nullable|boolean',
            'excluded_students' => 'nullable|array',
            'excluded_students.*' => 'exists:users,id',
        ]);

        if ($request->filled('tutor_id')) {
            $tutor = User::findOrFail($request->input('tutor_id'));
            if (! $tutor->hasRole('Tutor')) {
                return back()->with('error', 'El tutor seleccionado no tiene el rol de Tutor.');
            }
        }

        if ($request->filled('student_id')) {
            $student = User::findOrFail($request->input('student_id'));
            if (! $student->hasRole('Estudiante')) {
                return back()->with('error', 'El estudiante seleccionado no tiene el rol de Estudiante.');
            }
        }

        PeriodMilestone::create([
            'academic_period_id' => $period->id,
            'subject_id' => $request->input('subject_id'),
            'tutor_id' => $request->input('tutor_id'),
            'student_id' => $request->input('student_id'),
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'scheduled_date' => $request->input('scheduled_date'),
            'notify_tutor' => $request->has('notify_tutor'),
            'notify_jury' => $request->has('notify_jury'),
            'excluded_student_ids' => $request->input('excluded_students', []),
        ]);

        return redirect()->route('admin.periods.edit', [$period, 'tab' => 'milestones'])
            ->with('success', 'Actividad programada con éxito.');
    }

    public function destroyMilestone(AcademicPeriod $period, PeriodMilestone $milestone): RedirectResponse
    {
        if ($milestone->academic_period_id !== $period->id) {
            abort(403);
        }
        $milestone->delete();

        return redirect()->route('admin.periods.edit', [$period, 'tab' => 'milestones'])
            ->with('success', 'Actividad eliminada con éxito.');
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (empty($query)) {
            return response()->json([]);
        }

        $students = User::role('Estudiante')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('cedula', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'cedula']);

        return response()->json($students);
    }

    public function getStudentsUnderTutor(Request $request, AcademicPeriod $period): JsonResponse
    {
        $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $studentIds = Enrollment::where('academic_period_id', $period->id)
            ->where('subject_id', $request->input('subject_id'))
            ->where('tutor_id', $request->input('tutor_id'))
            ->pluck('student_id');

        $students = User::whereIn('id', $studentIds)
            ->get(['id', 'name', 'email', 'cedula']);

        return response()->json($students);
    }
}
