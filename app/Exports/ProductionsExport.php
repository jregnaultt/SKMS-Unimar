<?php

namespace App\Exports;

use App\Models\Production;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionsExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
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
     * Column headings for Excel.
     */
    public function headings(): array
    {
        return [
            'UUID',
            'Título de la Investigación',
            'Autores',
            'Tutor Académico',
            'Programa Académico',
            'Línea de Investigación',
            'Tipo de Producción',
            'Período Académico',
            'Estado del Workflow',
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
            $production->getStatusLabel(),
            $production->published_at ? $production->published_at->format('d/m/Y') : 'No publicado',
        ];
    }

    /**
     * Styles for the sheet.
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // 1. Style the header row (Row 1)
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0D4D98'], // Unimar Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Set row height for headers
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Format data cells
        for ($row = 2; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(22);

            // Alternating rows (zebra striping)
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'], // slate-50
                ]);
            }

            // Border around all cells
            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getBorders()->getAllBorders()->applyFromArray([
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'E2E8F0'], // slate-200
            ]);

            // Center the UUID, Type, Period, State, and Date Published
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$row}:J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Left align for title, authors, tutor, program, line
            $sheet->getStyle("B{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Add vertical alignment
            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        return [];
    }

    /**
     * Set explicit column widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // UUID
            'B' => 45, // Título
            'C' => 25, // Autores
            'D' => 25, // Tutor
            'E' => 25, // Programa Académico
            'F' => 25, // Línea de Investigación
            'G' => 22, // Tipo
            'H' => 18, // Período
            'I' => 18, // Estado
            'J' => 18, // Fecha Publicación
        ];
    }
}
