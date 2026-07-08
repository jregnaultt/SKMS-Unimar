<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Production;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminApprovalsController extends Controller
{
    /**
     * Display a listing of productions pending coordinator approval.
     */
    public function index(Request $request): View
    {
        $query = Production::where('workflow_state', 'under_coordinator_review')
            ->with(['subject', 'academicPeriod', 'users']);

        // Filter by academic period
        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->input('academic_period_id'));
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

        $productions = $query->latest()->paginate(10);
        $periods = AcademicPeriod::orderBy('end_date', 'desc')->get();

        return view('admin.approvals.index', compact('productions', 'periods'));
    }
}
