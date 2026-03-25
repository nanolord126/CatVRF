<?php declare(strict_

/**
 * Class
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new Class();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Traits
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope("tenant", function (Builder $builder): void {
            if (auth()->check() && auth()->user()) {
                $builder->where("tenant_id", "=", auth()->user()->tenant_id);
            }
        });
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function getTenantId(): ?int
    {
        return $this->getAttribute("tenant_id");
    }

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function setTenantId(?int $tenantId): self
    {
        $this->setAttribute("tenant_id", $tenantId);
        return $this;
    }
}

