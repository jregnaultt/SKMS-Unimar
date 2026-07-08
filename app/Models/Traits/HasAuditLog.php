<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait HasAuditLog
{
    /**
     * Boot the trait and register Eloquent model event listeners.
     */
    public static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            static::logAuditAction($model, 'create', [], $model->getAuditAttributes($model->getAttributes()));
        });

        static::updated(function ($model) {
            $changedKeys = array_keys($model->getChanges());
            $oldValues = [];
            $newValues = [];

            foreach ($changedKeys as $key) {
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $model->getAttribute($key);
            }

            $filteredOld = $model->getAuditAttributes($oldValues);
            $filteredNew = $model->getAuditAttributes($newValues);

            if (! empty($filteredNew)) {
                static::logAuditAction($model, 'update', $filteredOld, $filteredNew);
            }
        });

        static::deleted(function ($model) {
            static::logAuditAction($model, 'delete', $model->getAuditAttributes($model->getAttributes()), []);
        });
    }

    /**
     * Create an entry in the audit_logs table.
     */
    protected static function logAuditAction($model, string $action, array $oldValues, array $newValues): void
    {
        if (! Auth::check()) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action.'_'.strtolower(class_basename($model)),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip() ?? '',
        ]);
    }

    /**
     * Filter out ignored or sensitive attributes from the logged array.
     */
    protected function getAuditAttributes(array $attributes): array
    {
        $ignored = [
            'password',
            'remember_token',
            'google_access_token',
            'google_refresh_token',
            'google_token_expires_at',
            'created_at',
            'updated_at',
        ];

        if (isset($this->ignoredAuditAttributes)) {
            $ignored = array_merge($ignored, $this->ignoredAuditAttributes);
        }

        return array_filter(
            $attributes,
            fn ($key) => ! in_array($key, $ignored),
            ARRAY_FILTER_USE_KEY
        );
    }
}
