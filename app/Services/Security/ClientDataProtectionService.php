<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Client Data Protection Service
 * 
 * Comprehensive protection for client data against:
 * - Mass export/dumping
 * - Hunting (targeted extraction)
 * - Cross-tenant leakage
 * - Insider threats
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
final readonly class ClientDataProtectionService
{
    private const CACHE_TTL_MINUTES = 5;
    private const MAX_RECORDS_PER_REQUEST = 100;
    private const HUNTING_THRESHOLD = 3;
    private const RATE_LIMIT_WINDOW_MINUTES = 1;
    private const RATE_LIMIT_MAX_REQUESTS = 10;

    public function __construct(
        private readonly CooldownService $cooldownService,
        private readonly AuditService $auditService,
        private readonly DatabaseProtectionService $dbProtection,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
    ) {}

    /**
     * Check if user can access client data
     * 
     * @param  User  $user  User requesting access
     * @param  string  $operation  Operation type (view, export, search)
     * @return bool True if access allowed
     * @throws \RuntimeException If access denied
     */
    public function checkAccess(User $user, string $operation = 'view'): bool
    {
        // Check if under cooldown
        $actionType = match ($operation) {
            'export' => \App\Enums\CooldownActionType::DATA_EXPORT,
            default => \App\Enums\CooldownActionType::HUNTING_DETECTED,
        };

        if ($this->cooldownService->isUnderCooldown($user, $actionType)) {
            $remainingTime = $this->cooldownService->getRemainingTimeForHumans($user, $actionType);
            throw new \RuntimeException("Access denied: under cooldown for {$remainingTime}");
        }

        // Check rate limits
        if (! $this->checkRateLimit($user, $operation)) {
            throw new \RuntimeException('Access denied: rate limit exceeded');
        }

        // Check role-based permissions
        if (! $this->checkRolePermissions($user, $operation)) {
            throw new \RuntimeException('Access denied: insufficient permissions');
        }

        return true;
    }

    /**
     * Mask sensitive data in collection
     * 
     * @param  Collection  $collection  Collection of models
     * @param  array  $fields  Fields to mask
     * @return Collection Masked collection
     */
    public function maskCollection(Collection $collection, array $fields = ['email', 'phone', 'inn']): Collection
    {
        return $collection->map(function ($item) use ($fields) {
            foreach ($fields as $field) {
                if (isset($item->$field)) {
                    $item->$field = $this->dbProtection->maskData($item->$field, $field);
                }
            }

            return $item;
        });
    }

    /**
     * Detect hunting patterns in query
     * 
     * @param  Builder  $query  Query builder
     * @param  User  $user  User executing query
     * @return void
     */
    public function detectHuntingPattern(Builder $query, User $user): void
    {
        $wheres = $query->getQuery()->wheres;

        foreach ($wheres as $where) {
            if (isset($where['type']) && $where['type'] === 'Basic') {
                $column = $where['column'] ?? '';
                $value = $where['value'] ?? '';

                // Check for LIKE patterns on contact fields
                if ($this->isContactField($column) && $this->isPatternQuery($value)) {
                    $this->handleHuntingDetection($user, $column, $value);
                }
            }
        }
    }

    /**
     * Validate export request
     * 
     * @param  User  $user  User requesting export
     * @param  int  $recordCount  Number of records to export
     * @return bool True if export allowed
     * @throws \RuntimeException If export denied
     */
    public function validateExport(User $user, int $recordCount): bool
    {
        // Check access
        $this->checkAccess($user, 'export');

        // Check record count limit
        $maxRecords = $this->getMaxExportRecords($user);
        if ($recordCount > $maxRecords) {
            $this->auditService->logEvent('export_limit_exceeded', [
                'user_id' => $user->id,
                'requested_records' => $recordCount,
                'max_records' => $maxRecords,
            ], 'security');

            throw new \RuntimeException("Export denied: maximum {$maxRecords} records allowed");
        }

        // Check for super-admin approval for large exports
        if ($recordCount > 50 && ! $user->role?->isPlatformAdmin()) {
            throw new \RuntimeException('Export denied: large exports require super-admin approval');
        }

        return true;
    }

    /**
     * Log data access for audit
     * 
     * @param  User  $user  User accessing data
     * @param  string  $action  Action performed
     * @param  array  $metadata  Additional metadata
     * @return void
     */
    public function logDataAccess(User $user, string $action, array $metadata = []): void
    {
        $this->auditService->logEvent('client_data_access', array_merge([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action' => $action,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ], $metadata), 'security');
    }

    /**
     * Check rate limit for user
     */
    private function checkRateLimit(User $user, string $operation): bool
    {
        $cacheKey = "rate_limit:{$user->id}:{$operation}";
        $requests = $this->cache->get($cacheKey, 0);

        if ($requests >= self::RATE_LIMIT_MAX_REQUESTS) {
            return false;
        }

        $this->cache->put($cacheKey, $requests + 1, self::RATE_LIMIT_WINDOW_MINUTES * 60);

        return true;
    }

    /**
     * Check role-based permissions
     */
    private function checkRolePermissions(User $user, string $operation): bool
    {
        return match ($operation) {
            'export' => $user->role?->isPlatformAdmin(),
            'search' => $user->role?->isPlatformAdmin() || $user->role?->isBusiness(),
            'view' => true, // Basic view allowed for all authenticated users
            default => false,
        };
    }

    /**
     * Check if field is a contact field
     */
    private function isContactField(string $field): bool
    {
        return in_array($field, ['email', 'phone', 'inn', 'first_name', 'last_name']);
    }

    /**
     * Check if value contains pattern matching
     */
    private function isPatternQuery(string $value): bool
    {
        return str_contains($value, '%') || str_contains($value, '_');
    }

    /**
     * Handle hunting detection
     */
    private function handleHuntingDetection(User $user, string $column, string $pattern): void
    {
        $cacheKey = "hunting_detection:{$user->id}:{$column}";
        $attempts = $this->cache->get($cacheKey, 0);

        if ($attempts >= self::HUNTING_THRESHOLD) {
            // Trigger cooldown
            $this->cooldownService->startCooldown(
                $user,
                \App\Enums\CooldownActionType::HUNTING_DETECTED,
                1, // 1 hour
                "Hunting pattern detected on {$column}"
            );

            // Log to audit
            $this->auditService->logEvent('hunting_pattern_blocked', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'column' => $column,
                'pattern' => $pattern,
                'attempts' => $attempts,
            ], 'security');

            // Log to security alert
            $this->logger->channel('security_alert')->warning('Hunting pattern blocked', [
                'user_id' => $user->id,
                'column' => $column,
                'pattern' => $pattern,
            ]);

            $this->cache->forget($cacheKey);
        } else {
            $this->cache->put($cacheKey, $attempts + 1, self::CACHE_TTL_MINUTES * 60);

            // Log warning
            $this->auditService->logEvent('hunting_pattern_detected', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'column' => $column,
                'pattern' => $pattern,
                'attempts' => $attempts + 1,
            ], 'security');
        }
    }

    /**
     * Get maximum export records for user
     */
    private function getMaxExportRecords(User $user): int
    {
        return match (true) {
            $user->role?->isPlatformAdmin() => 1000,
            $user->role?->isBusiness() => 200,
            default => 50,
        };
    }

    /**
     * Enforce result limit on query
     * 
     * @param  Builder  $query  Query builder
     * @param  User  $user  User executing query
     * @return Builder Query with limit applied
     */
    public function enforceResultLimit(Builder $query, User $user): Builder
    {
        $limit = match (true) {
            $user->role?->isPlatformAdmin() => 1000,
            $user->role?->isBusiness() => 200,
            default => 50,
        };

        return $query->limit($limit);
    }

    /**
     * Anonymize data for export
     * 
     * @param  array  $data  Data to anonymize
     * @param  array  $sensitiveFields  Fields to anonymize
     * @return array Anonymized data
     */
    public function anonymizeForExport(array $data, array $sensitiveFields = ['email', 'phone', 'inn']): array
    {
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->dbProtection->maskData($data[$field], $field);
            }
        }

        return $data;
    }

    /**
     * Check for cross-tenant access attempt
     * 
     * @param  User  $user  User attempting access
     * @param  int  $targetTenantId  Target tenant ID
     * @return bool True if access is allowed
     * @throws \RuntimeException If cross-tenant access denied
     */
    public function checkCrossTenantAccess(User $user, int $targetTenantId): bool
    {
        // Super-admins can access all tenants
        if ($user->role?->isPlatformAdmin()) {
            return true;
        }

        // Users can only access their own tenant
        if ($user->tenant_id !== $targetTenantId) {
            $this->auditService->logEvent('cross_tenant_access_attempt', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'target_tenant_id' => $targetTenantId,
            ], 'security');

            throw new \RuntimeException('Access denied: cross-tenant access not allowed');
        }

        return true;
    }
}
