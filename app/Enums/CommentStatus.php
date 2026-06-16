<?php

namespace App\Enums;

enum CommentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Addressed = 'addressed';

    /**
     * Returns the human-readable label in Spanish.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En Progreso',
            self::Addressed => 'Atendido',
        };
    }

    /**
     * Returns Tailwind CSS badge classes for each status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-red-100 text-red-700 border border-red-300',
            self::InProgress => 'bg-yellow-100 text-yellow-700 border border-yellow-300',
            self::Addressed => 'bg-green-100 text-green-700 border border-green-300',
        };
    }

    /**
     * Returns the left-border Tailwind class for comment cards.
     */
    public function borderClass(): string
    {
        return match ($this) {
            self::Pending => 'border-l-4 border-red-500',
            self::InProgress => 'border-l-4 border-yellow-500',
            self::Addressed => 'border-l-4 border-green-500',
        };
    }

    /**
     * Returns the valid next statuses from the current one.
     *
     * @return array<int, self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress],
            self::InProgress => [self::Addressed],
            self::Addressed => [],
        };
    }

    /**
     * Whether a transition to the given status is valid.
     */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->nextStatuses(), true);
    }
}
