<?php

declare(strict_types=1);

namespace Modules\Common\Contracts;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

interface TechnicalVerticalServiceInterface
{
    public function forTenant(Tenant $tenant): self;

    public function getTenantScope(): ?Builder;

    public function isEnabled(): bool;

    public function getCorrelationId(): ?string;

    public function withCorrelationId(string $correlationId): self;
}
