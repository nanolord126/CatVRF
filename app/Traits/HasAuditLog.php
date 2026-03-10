<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Trait for automatic audit logging on create, update, delete for Zero Trust 2026.
 */
trait HasAuditLog
{
    public static function bootHasAuditLog()
    {
        static::created(function ($model) {
            static::logAuditAction('created', $model);
        });

        static::updated(function ($model) {
            static::logAuditAction('updated', $model);
        });

        static::deleted(function ($model) {
            static::logAuditAction('deleted', $model);
        });
    }

    protected static function logAuditAction(string $event, $model)
    {
        Log::channel('audit')->info("Model Audit Action", [
            'event' => $event,
            'model' => get_class($model),
            'id' => $model->id,
            'tenant_id' => $model->tenant_id ?? null,
            'correlation_id' => $model->correlation_id ?? null,
            'user_id' => auth()->id(),
            'changes' => $event === 'updated' ? $model->getChanges() : null
        ]);
    }
}
