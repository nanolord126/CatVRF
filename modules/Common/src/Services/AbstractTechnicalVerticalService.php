<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\Common\Contracts\TechnicalVerticalServiceInterface;

abstract class AbstractTechnicalVerticalService implements TechnicalVerticalServiceInterface
{
    protected ?Tenant $tenant = null;
    protected ?string $correlationId = null;

    public function forTenant(Tenant $tenant): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    public function getTenantScope(): ?Builder
    {
        if (!$this->tenant) {
            // Or throw an exception, based on strictness policy
            return null;
        }
        // This assumes a 'tenant_id' field on the model.
        // Adjust if your tenancy setup is different.
        return $this->getModelQuery()->where('tenant_id', $this->tenant->id);
    }

    public function isEnabled(): bool
    {
        // Implement feature flag logic, e.g., using a config or a database setting
        return true;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;
        return $this;
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('audit')->info($message, $this->getLogContext($context));
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel('audit')->error($message, $this->getLogContext($context));
    }

    private function getLogContext(array $context = []): array
    {
        return array_merge($context, [
            'correlation_id' => $this->correlationId,
            'tenant_id' => $this->tenant?->id,
            'service' => static::class,
        ]);
    }

    /**
     * This method should be implemented by concrete services
     * to return a query builder for their primary model.
     */
    abstract protected function getModelQuery(): Builder;
}
