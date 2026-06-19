<?php

namespace App\Exports;

use App\Models\Production;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly array $filters) {}

    /**
     * Query to fetch productions for the report.
     */
    public function query()
    {
        $query = Production::query()->with(['academicProgram', 'researchLine', 'productionType', 'academicPeriod']);

        if (! empty($this->filters['program_id'])) {
            $query->where('academic_program_id', $this->filters['program_id']);
        }
        if (! empty($this->filters['period_id'])) {
            $query->where('academic_period_id', $this->filters['period_id']);
        }
        if (! empty($this->filters['state'])) {
            $query->where('workflow_state', $this->filters['state']);
        }

        return $query;
    }

    /**
     * Column headers for Excel.
     */
    public function headings(): array
    {
        return [
            'UUID',
            'Título',
            'Autores',
            'Tutor',
            'Programa Académico',
            'Línea de Investigación',
            'Tipo',
            'Período',
            'Estado',
            'Fecha Publicación',
        ];
    }

    /**
     * Map production records to row columns.
     *
     * @param  mixed  $production
     */
    public function map($production): array
    {
        return [
            $production->uuid,
            $production->title,
            $production->authors,
            $production->tutor,
            $production->academicProgram->name ?? 'N/A',
            $production->researchLine->name ?? 'N/A',
            $production->productionType->name ?? 'N/A',
            $production->academicPeriod->name ?? 'N/A',
            $production->workflow_state,
            $production->published_at ? $production->published_at->format('Y-m-d') : 'No publicado',
        ];
    }
}
