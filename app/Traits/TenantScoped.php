<?php declare(strict_types=1);

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

    public function getTenantId(): ?int
    {
        return $this->getAttribute("tenant_id");
    }

    public function setTenantId(?int $tenantId): self
    {
        $this->setAttribute("tenant_id", $tenantId);
        return $this;
    }
}

