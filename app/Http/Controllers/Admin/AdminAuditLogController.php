<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditLogsExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AuditLog;
use App\Models\Production;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminAuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request): View
    {
        $query = $this->getFilteredQuery($request);

        $logs = $query->latest()->paginate(15);

        // Fetch values for filters
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $users = User::whereHas('auditLogs')->orderBy('name')->get();
        $periods = AcademicPeriod::orderBy('name', 'desc')->get();
        $tutors = Production::whereNotNull('tutor')
            ->where('tutor', '!=', '')
            ->distinct()
            ->orderBy('tutor')
            ->pluck('tutor');

        return view('admin.audit-logs.index', compact('logs', 'actions', 'users', 'periods', 'tutors'));
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json($auditLog->load('user'));
    }

    /**
     * Export audit logs to Excel.
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'search', 'user_id', 'action_type', 'date_from', 'date_to', 'academic_period_id', 'tutor',
        ]);

        return Excel::download(
            new AuditLogsExport($filters),
            'bitacora_auditoria_'.now()->format('Y-m-d_H-i-s').'.xlsx'
        );
    }

    /**
     * Helper to get filtered query for index and export.
     */
    protected function getFilteredQuery(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action_type')) {
            $query->where('action', $request->input('action_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('academic_period_id')) {
            $periodId = $request->input('academic_period_id');
            $query->where(function ($q) use ($periodId) {
                $q->where(function ($sub) use ($periodId) {
                    $sub->where('auditable_type', 'App\Models\Production')
                        ->whereIn('auditable_id', function ($subQuery) use ($periodId) {
                            $subQuery->select('id')->from('productions')->where('academic_period_id', $periodId);
                        });
                })->orWhere(function ($sub) use ($periodId) {
                    $sub->where('auditable_type', 'App\Models\Revision')
                        ->whereIn('auditable_id', function ($subQuery) use ($periodId) {
                            $subQuery->select('id')->from('revisions')->whereIn('production_id', function ($spq) use ($periodId) {
                                $spq->select('id')->from('productions')->where('academic_period_id', $periodId);
                            });
                        });
                })->orWhere(function ($sub) use ($periodId) {
                    $sub->where('auditable_type', 'App\Models\Comment')
                        ->whereIn('auditable_id', function ($subQuery) use ($periodId) {
                            $subQuery->select('id')->from('comments')->whereIn('production_id', function ($spq) use ($periodId) {
                                $spq->select('id')->from('productions')->where('academic_period_id', $periodId);
                            });
                        });
                })->orWhere(function ($sub) use ($periodId) {
                    $sub->where('auditable_type', 'App\Models\DocumentVersion')
                        ->whereIn('auditable_id', function ($subQuery) use ($periodId) {
                            $subQuery->select('id')->from('document_versions')->whereIn('production_id', function ($spq) use ($periodId) {
                                $spq->select('id')->from('productions')->where('academic_period_id', $periodId);
                            });
                        });
                });
            });
        }

        if ($request->filled('tutor')) {
            $tutorName = $request->input('tutor');
            $query->where(function ($q) use ($tutorName) {
                $q->where(function ($sub) use ($tutorName) {
                    $sub->where('auditable_type', 'App\Models\Production')
                        ->whereIn('auditable_id', function ($subQuery) use ($tutorName) {
                            $subQuery->select('id')->from('productions')->where('tutor', 'like', "%{$tutorName}%");
                        });
                })->orWhere(function ($sub) use ($tutorName) {
                    $sub->where('auditable_type', 'App\Models\Revision')
                        ->whereIn('auditable_id', function ($subQuery) use ($tutorName) {
                            $subQuery->select('id')->from('revisions')->whereIn('production_id', function ($spq) use ($tutorName) {
                                $spq->select('id')->from('productions')->where('tutor', 'like', "%{$tutorName}%");
                            });
                        });
                })->orWhere(function ($sub) use ($tutorName) {
                    $sub->where('auditable_type', 'App\Models\Comment')
                        ->whereIn('auditable_id', function ($subQuery) use ($tutorName) {
                            $subQuery->select('id')->from('comments')->whereIn('production_id', function ($spq) use ($tutorName) {
                                $spq->select('id')->from('productions')->where('tutor', 'like', "%{$tutorName}%");
                            });
                        });
                })->orWhere(function ($sub) use ($tutorName) {
                    $sub->where('auditable_type', 'App\Models\DocumentVersion')
                        ->whereIn('auditable_id', function ($subQuery) use ($tutorName) {
                            $subQuery->select('id')->from('document_versions')->whereIn('production_id', function ($spq) use ($tutorName) {
                                $spq->select('id')->from('productions')->where('tutor', 'like', "%{$tutorName}%");
                            });
                        });
                });
            });
        }

        return $query;
    }
}
