<?php

declare(strict_types=1);

namespace App\Domains\Audit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Domains\Audit\Events\ModelCreatedEvent;
use App\Domains\Audit\Events\ModelUpdatedEvent;
use App\Domains\Audit\Events\ModelDeletedEvent;

/**
 * Auditable — Trait for models with automatic audit logging.
 * Automatically dispatches domain events on model mutations.
 *
 * Usage:
 *   use App\Domains\Audit\Traits\Auditable;
 *
 *   class Order extends Model {
 *       use Auditable;
 *   }
 *
 * @mixin Model
 */
trait Auditable
{
    /**
     * Boot the auditable trait.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            if (! static::isAuditingEnabled()) {
                return;
            }

            ModelCreatedEvent::dispatch(
                model: $model,
                correlationId: static::getCorrelationId(),
            );
        });

        static::updated(function (Model $model) {
            if (! static::isAuditingEnabled()) {
                return;
            }

            $changes = $model->getDirty();
            $original = $model->getOriginal();

            $oldValues = [];
            $newValues = [];

            foreach ($changes as $key => $value) {
                if (static::shouldAuditField($key)) {
                    $oldValues[$key] = $original[$key] ?? null;
                    $newValues[$key] = $value;
                }
            }

            if (empty($oldValues)) {
                return;
            }

            ModelUpdatedEvent::dispatch(
                model: $model,
                oldValues: $oldValues,
                newValues: $newValues,
                correlationId: static::getCorrelationId(),
            );
        });

        static::deleted(function (Model $model) {
            if (! static::isAuditingEnabled()) {
                return;
            }

            ModelDeletedEvent::dispatch(
                model: $model,
                deletedValues: $model->getAttributes(),
                correlationId: static::getCorrelationId(),
            );
        });
    }

    /**
     * Check if auditing is enabled for this model.
     */
    protected static function isAuditingEnabled(): bool
    {
        if (isset(static::$auditingDisabled) && static::$auditingDisabled === true) {
            return false;
        }

        return config('audit.enabled', true);
    }

    /**
     * Check if a field should be audited.
     */
    protected static function shouldAuditField(string $field): bool
    {
        $excludedFields = static::$auditExcludedFields ?? config('audit.excluded_fields', [
            'id', 'created_at', 'updated_at', 'remember_token',
            'password', 'password_confirmation',
        ]);

        return ! in_array($field, $excludedFields, true);
    }

    /**
     * Get correlation ID from context or generate new one.
     */
    protected static function getCorrelationId(): string
    {
        if (Auth::check() && Auth::user()?->correlation_id) {
            return Auth::user()->correlation_id;
        }

        return (string) Str::uuid();
    }

    /**
     * Disable auditing for this model instance.
     */
    public function disableAuditing(): void
    {
        static::$auditingDisabled = true;
    }

    /**
     * Enable auditing for this model instance.
     */
    public function enableAuditing(): void
    {
        static::$auditingDisabled = false;
    }
}
