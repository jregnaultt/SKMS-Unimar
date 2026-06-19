<?php

namespace App\Jobs;

use App\Events\ReportGenerated;
use App\Exports\ProductionsExport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $type, // 'pdf' | 'excel'
        public array $filters
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fileName = 'report_'.Str::random(16).($this->type === 'pdf' ? '.pdf' : '.xlsx');
        $filePath = 'reports/'.$fileName;

        if ($this->type === 'excel') {
            Excel::store(new ProductionsExport($this->filters), $filePath, 'local');
        } else {
            // Fetch filtered productions
            $productions = (new ProductionsExport($this->filters))->query()->get();
            $pdf = DomPdf::loadView('reports.productivity', [
                'productions' => $productions,
                'user' => $this->user,
            ]);
            Storage::disk('local')->put($filePath, $pdf->output());
        }

        // Broadcast the report generated event
        broadcast(new ReportGenerated($this->user->id, $fileName));
    }
}
