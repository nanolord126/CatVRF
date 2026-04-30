<?php

declare(strict_types=1);

namespace App\Features;

use LogManager;

use Psr\Log\LoggerInterface;

use Laravel\Pennant\Feature;
use Illuminate\Log\LogManager;

/**
 * Base Vertical Feature Class
 *
 * Abstract base class for all vertical feature flags.
 * Provides common rollout strategies and safety checks.
 */
abstract class BaseVerticalFeature
{
    /**
     * Determine if the feature should be active.
     * Default to false for safety - requires explicit activation.
     */
    public function __construct(private readonly LogManager $logManager,
        private readonly LoggerInterface $logger,) {}

    public function resolve(): bool
    {
        return false;
    }

    /**
     * Get the vertical name for this feature.
     */
    abstract protected function getVerticalName(): string;

    /**
     * Get the feature name.
     */
    protected function getFeatureName(): string
    {
        return strtolower(str_replace('\\', '-', static::class));
    }

    /**
     * Percentage-based rollout.
     */
    protected function rolloutPercentage(int $percentage): bool
    {
        $hash = crc32(request()->ip().request()->userAgent().$this->getFeatureName());

        return ($hash % 100) < $percentage;
    }

    /**
     * Tenant-based rollout.
     */
    protected function rolloutForTenant(int $tenantId): bool
    {
        $betaTenants = $this->getBetaTenants();

        return in_array($tenantId, $betaTenants, true);
    }

    /**
     * User-based rollout.
     */
    protected function rolloutForUser(int $userId): bool
    {
        $betaUsers = $this->getBetaUsers();

        return in_array($userId, $betaUsers, true);
    }

    /**
     * Get beta tenants for this vertical.
     * Override in child class for specific tenants.
     */
    protected function getBetaTenants(): array
    {
        return [];
    }

    /**
     * Get beta users for this vertical.
     * Override in child class for specific users.
     */
    protected function getBetaUsers(): array
    {
        return [];
    }

    /**
     * Safety check before activation.
     * Override in child class for vertical-specific checks.
     */
    protected function canActivate(): bool
    {
        return true;
    }

    /**
     * Log feature activation/deactivation.
     */
    protected function logFeature(string $action, ?int $userId = null, ?int $tenantId = null): void
    {
        $this->logManager /* TODO: inject via constructor DI */ /* TODO: inject via DI */->$this->logger->info("Feature {$action}", [
            'feature' => $this->getFeatureName(),
            'vertical' => $this->getVerticalName(),
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'ip' => request()->ip(),
        ]);
    }
}
