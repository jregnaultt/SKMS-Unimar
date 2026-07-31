<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['action_label'];

    public function getActionLabelAttribute(): string
    {
        return $this->getActionLabel();
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get user-friendly Spanish label for the audited action.
     */
    public function getActionLabel(): string
    {
        $action = $this->action;

        $translations = [
            'create_subjecttutorperiod' => 'Asignar Tutor en Período',
            'update_subjecttutorperiod' => 'Actualizar Tutor en Período',
            'delete_subjecttutorperiod' => 'Eliminar Tutor en Período',

            'create_enrollment' => 'Matricular Estudiante',
            'update_enrollment' => 'Actualizar Matrícula',
            'delete_enrollment' => 'Eliminar Matrícula',

            'create_production' => 'Crear Producción Científica',
            'update_production' => 'Actualizar Producción Científica',
            'delete_production' => 'Eliminar Producción Científica',

            'create_documentversion' => 'Cargar Nueva Versión de Documento',
            'update_documentversion' => 'Actualizar Versión de Documento',
            'delete_documentversion' => 'Eliminar Versión de Documento',

            'create_revision' => 'Registrar Revisión de Workflow',
            'workflow_transition' => 'Cambio de Estado (Workflow)',

            'create_periodmilestone' => 'Crear Hito de Período Académico',
            'update_periodmilestone' => 'Actualizar Hito de Período Académico',
            'delete_periodmilestone' => 'Eliminar Hito de Período Académico',

            'create_productionmilestone' => 'Crear Hito de Producción',
            'update_productionmilestone' => 'Actualizar Hito de Producción',
            'delete_productionmilestone' => 'Eliminar Hito de Producción',

            'login' => 'Inicio de Sesión',
            'logout' => 'Cierre de Sesión',
        ];

        if (isset($translations[$action])) {
            return $translations[$action];
        }

        if (str_starts_with($action, 'create_')) {
            return 'Crear '.ucwords(str_replace('_', ' ', substr($action, 7)));
        }
        if (str_starts_with($action, 'update_')) {
            return 'Actualizar '.ucwords(str_replace('_', ' ', substr($action, 7)));
        }
        if (str_starts_with($action, 'delete_')) {
            return 'Eliminar '.ucwords(str_replace('_', ' ', substr($action, 7)));
        }

        return ucwords(str_replace('_', ' ', $action));
    }
}
