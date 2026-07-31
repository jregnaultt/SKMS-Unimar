<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly array $filters = []) {}

    /**
     * Query to fetch audit logs for the report.
     */
    public function query()
    {
        $query = AuditLog::query()->with('user');

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (! empty($this->filters['action_type'])) {
            $query->where('action', $this->filters['action_type']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['academic_period_id'])) {
            $periodId = $this->filters['academic_period_id'];
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

        if (! empty($this->filters['tutor'])) {
            $tutorName = $this->filters['tutor'];
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

        // Limit to prevent memory crash on huge table
        return $query->latest()->limit(1000);
    }

    /**
     * Column headings for Excel.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Usuario / Actor',
            'Acción',
            'Entidad Afectada',
            'Dirección IP',
            'Modificaciones Realizadas',
            'Fecha y Hora',
        ];
    }

    /**
     * Map audit log records to row columns.
     *
     * @param  mixed  $log
     */
    public function map($log): array
    {
        // Format modifications dynamically to prevent cell size limit crashes and keep it clean
        $changes = [];
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        foreach ($new as $key => $val) {
            $oldVal = is_array($old[$key] ?? null) ? json_encode($old[$key]) : ($old[$key] ?? 'N/A');
            $newVal = is_array($val) ? json_encode($val) : $val;

            // Limit string lengths to prevent massive cell bloat
            $oldValStr = strlen($oldVal) > 50 ? substr($oldVal, 0, 47).'...' : $oldVal;
            $newValStr = strlen($newVal) > 50 ? substr($newVal, 0, 47).'...' : $newVal;

            if ($oldValStr !== $newValStr) {
                $changes[] = "{$key}: {$oldValStr} -> {$newValStr}";
            }
        }

        $changesString = empty($changes) ? 'Ninguna modificación de atributos detectada' : implode(' | ', $changes);
        $entity = $log->auditable_type ? (basename(str_replace('\\', '/', $log->auditable_type)).' #'.$log->auditable_id) : 'Sistema';

        return [
            $log->id,
            $log->user->name ?? 'Sistema / Visitante',
            $log->getActionLabel(),
            $entity,
            $log->ip_address ?? 'N/A',
            $changesString,
            $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

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

            // Center the ID, IP, and Date
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Left align for User, Action, Entity, Changes
            $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Vertical alignment
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
            'A' => 10, // ID
            'B' => 30, // Usuario
            'C' => 35, // Acción
            'D' => 22, // Entidad
            'E' => 18, // IP
            'F' => 60, // Cambios
            'G' => 20, // Fecha
        ];
    }
}
