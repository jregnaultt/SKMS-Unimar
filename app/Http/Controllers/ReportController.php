<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportJob;
use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reports panel dashboard.
     */
    public function index(): View
    {
        $programs = AcademicProgram::where('is_active', true)->orderBy('name')->get();
        $periods = AcademicPeriod::where('is_active', true)->orderBy('name', 'desc')->get();

        return view('admin.reports.index', compact('programs', 'periods'));
    }

    /**
     * Dispatch the async report generation job.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:pdf,excel',
            'program_id' => 'nullable|exists:academic_programs,id',
            'period_id' => 'nullable|exists:academic_periods,id',
            'state' => 'nullable|string',
        ]);

        GenerateReportJob::dispatch(
            $request->user(),
            $request->type,
            $request->only(['program_id', 'period_id', 'state'])
        );

        return response()->json([
            'status' => 'queued',
            'message' => 'El reporte se está procesando en segundo plano.',
        ]);
    }

    /**
     * Download a generated report securely.
     */
    public function download(string $filename)
    {
        $path = 'reports/'.$filename;
        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Reporte no encontrado.');
        }

        return Storage::disk('local')->download($path);
    }
}
