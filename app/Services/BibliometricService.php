<?php

namespace App\Services;

use App\Models\Production;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates institutional scientific production metrics for dashboards and reports.
 */
class BibliometricService
{
    /**
     * Total published productions in the repository.
     */
    public function totalPublished(): int
    {
        return Production::published()->count();
    }

    /**
     * Productions grouped by academic period.
     *
     * @return array<int, array{period: string, total: int}>
     */
    public function productivityByPeriod(): array
    {
        return Production::published()
            ->select('academic_period_id', DB::raw('count(*) as total'))
            ->groupBy('academic_period_id')
            ->with('academicPeriod')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->academicPeriod?->name ?? 'Sin período',
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Productions grouped by academic program.
     *
     * @return array<int, array{program: string, total: int}>
     */
    public function productivityByProgram(): array
    {
        return Production::published()
            ->select('academic_program_id', DB::raw('count(*) as total'))
            ->groupBy('academic_program_id')
            ->with('academicProgram')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn ($row) => [
                'program' => $row->academicProgram?->name ?? 'Sin programa',
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Productions grouped by research line.
     *
     * @return array<int, array{line: string, total: int}>
     */
    public function productivityByResearchLine(): array
    {
        return Production::published()
            ->select('research_line_id', DB::raw('count(*) as total'))
            ->groupBy('research_line_id')
            ->with('researchLine')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn ($row) => [
                'line' => $row->researchLine?->name ?? 'Sin línea',
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Top tutors by number of published theses.
     *
     * @return array<int, array{tutor: string, total: int}>
     */
    public function topTutors(int $limit = 10): array
    {
        return Production::published()
            ->select('tutor', DB::raw('count(*) as total'))
            ->whereNotNull('tutor')
            ->where('tutor', '!=', '')
            ->groupBy('tutor')
            ->orderBy('total', 'desc')
            ->orderBy('tutor')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'tutor' => $row->tutor,
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Top research lines by number of published theses.
     *
     * @return array<int, array{line: string, total: int}>
     */
    public function topResearchLines(int $limit = 10): array
    {
        return Production::published()
            ->select('research_line_id', DB::raw('count(*) as total'))
            ->with('researchLine')
            ->groupBy('research_line_id')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'line' => $row->researchLine?->name ?? 'Sin línea',
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Yearly evolution of published productions.
     *
     * @return array<int, array{year: int, total: int}>
     */
    public function yearlyEvolution(): array
    {
        return Production::published()
            ->select(DB::raw('CAST(strftime(\'%Y\', published_at) AS INTEGER) as year'), DB::raw('count(*) as total'))
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->year,
                'total' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Consolidated dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'total_published' => $this->totalPublished(),
            'by_period' => $this->productivityByPeriod(),
            'by_program' => $this->productivityByProgram(),
            'by_research_line' => $this->productivityByResearchLine(),
            'top_tutors' => $this->topTutors(),
            'top_research_lines' => $this->topResearchLines(),
            'yearly_evolution' => $this->yearlyEvolution(),
        ];
    }
}
