<?php

namespace App\Policies;

use App\Models\Production;
use App\Models\User;

class ProductionPolicy
{
    /**
     * Determine if the user can view the progress dashboard.
     */
    public function viewProgress(User $user, Production $production): bool
    {
        $isAuthor = $production->users()
            ->where('users.id', $user->id)
            ->where('production_user.role', 'author')
            ->exists();

        // Si ya está publicado, únicamente el autor (estudiante) puede seguir viéndolo.
        if ($production->workflow_state === 'published') {
            return $isAuthor;
        }

        // Si no está publicado, se aplican las reglas normales.
        if ($user->hasRole(['Coordinador', 'Super Admin', 'Decano'])) {
            return true;
        }

        // Tutors/Juries/Authors asignados pueden verlo.
        return $production->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine if the user can configure milestones.
     */
    public function manageMilestones(User $user, Production $production): bool
    {
        // No se pueden configurar hitos si el trabajo ya está publicado.
        if ($production->workflow_state === 'published') {
            return false;
        }

        // Solo la coordinación/admin puede gestionar hitos de trabajos activos.
        return $user->hasRole(['Coordinador', 'Super Admin', 'Decano']);
    }
}
