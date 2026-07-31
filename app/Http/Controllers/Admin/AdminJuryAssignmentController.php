<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Production;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminJuryAssignmentController extends Controller
{
    /**
     * Display a listing of productions for jury assignment.
     */
    public function index(Request $request): View
    {
        $query = Production::whereHas('subject', function ($q) {
            $q->where('code', 'like', 'TRI12%');
        })->with(['subject', 'academicPeriod', 'users']);

        // Filter by academic period
        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->input('academic_period_id'));
        }

        // Filter by assignment status (assigned / pending)
        if ($request->filled('status')) {
            if ($request->input('status') === 'assigned') {
                $query->whereHas('users', function ($q) {
                    $q->where('production_user.role', 'jury');
                });
            } elseif ($request->input('status') === 'pending') {
                $query->whereDoesntHave('users', function ($q) {
                    $q->where('production_user.role', 'jury');
                });
            }
        }

        // Filter by search query (title or student/author name)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('authors', 'like', "%{$search}%")
                    ->orWhereHas('users', function ($uq) use ($search) {
                        $uq->where('users.name', 'like', "%{$search}%")
                            ->where('production_user.role', 'author');
                    });
            });
        }

        // Apply Sorting
        $sortBy = $request->input('sort_by', 'subject_id');
        $sortDir = $request->input('sort_direction', 'asc');
        $allowedSorts = ['title', 'authors', 'workflow_state', 'subject_id', 'created_at'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'subject_id';
        }
        if (! in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $query->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'title') {
            $query->orderBy('title', 'asc');
        }

        $productions = $query->paginate(10)->withQueryString();
        $juries = User::role('Jurado')->orderBy('name')->get();
        $periods = AcademicPeriod::orderBy('end_date', 'desc')->get();

        return view('admin.juries.index', compact('productions', 'juries', 'periods'));
    }

    /**
     * Assign or update the jury for a production.
     */
    public function assign(Request $request, Production $production): RedirectResponse
    {
        $request->validate([
            'jury_1_id' => 'nullable|exists:users,id|different:jury_2_id',
            'jury_2_id' => 'nullable|exists:users,id|different:jury_1_id',
        ]);

        $tutorId = $production->users()->wherePivot('role', 'tutor')->value('users.id');

        if ($tutorId) {
            if ($request->input('jury_1_id') == $tutorId) {
                return redirect()->back()->withInput()->withErrors(['jury_1_id' => 'El tutor asignado al trabajo no puede ser jurado del mismo.']);
            }
            if ($request->input('jury_2_id') == $tutorId) {
                return redirect()->back()->withInput()->withErrors(['jury_2_id' => 'El tutor asignado al trabajo no puede ser jurado del mismo.']);
            }
        }

        DB::transaction(function () use ($request, $production) {
            // Remove existing jury role for this production
            $production->users()->wherePivot('role', 'jury')->detach();

            // Attach new juries if selected
            if ($request->filled('jury_1_id')) {
                $production->users()->attach($request->input('jury_1_id'), ['role' => 'jury']);
            }
            if ($request->filled('jury_2_id')) {
                $production->users()->attach($request->input('jury_2_id'), ['role' => 'jury']);
            }
        });

        return redirect()->route('admin.juries.index')
            ->with('success', 'Jurados asignados con éxito a la tesis.');
    }
}
