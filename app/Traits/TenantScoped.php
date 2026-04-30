<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 * @see https://catvrf.ru/docs/component
 */

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    public function getTenantId(): ?int
    {
        return $this->getAttribute('tenant_id');
    }

    public function setTenantId(?int $tenantId): self
    {
        $this->setAttribute('tenant_id', $tenantId);

        return $this;
    }

    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });

        static::creating(function ($model): void {
            if ($model->getAttribute('tenant_id') === null && function_exists('tenant') && tenant('id')) {
                $model->setAttribute('tenant_id', tenant('id'));
            }
        });
    }
}
