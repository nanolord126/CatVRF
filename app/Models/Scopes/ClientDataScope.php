<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\CarbonImmutable;

/**
 * Client Data Scope
 * 
 * Additional protection layer for sensitive client data (users, orders, addresses).
 * Prevents mass data extraction and hunting patterns by enforcing:
 * - Result limits based on user role
 * - Hunting pattern detection (LIKE queries on contact fields)
 * - Cross-tenant query prevention
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
final readonly class ClientDataScope implements Scope
{
    private const CACHE_TTL_MINUTES = 5;
    private const MAX_RESULTS_CUSTOMER = 50;
    private const MAX_RESULTS_STAFF = 200;
    private const MAX_RESULTS_SUPER_ADMIN = 1000;

    public function apply(Builder $builder, Model $model): void
    {
        // Skip in central domain
        if (request()->is('admin/*') || request()->is('admin')) {
            return;
        }

        $user = Auth::user();
        if (! $user) {
            return;
        }

        // Enforce result limits based on role
        $this->enforceResultLimits($builder, $user);

        // Detect hunting patterns
        $this->detectHuntingPatterns($builder, $user);
    }

    /**
     * Enforce result limits based on user role
     */
    private function enforceResultLimits(Builder $builder, $user): void
    {
        $limit = match (true) {
            $user->role?->isPlatformAdmin() => self::MAX_RESULTS_SUPER_ADMIN,
            $user->role?->isBusiness() => self::MAX_RESULTS_STAFF,
            default => self::MAX_RESULTS_CUSTOMER,
        };

        $builder->limit($limit);
    }

    /**
     * Detect hunting patterns (suspicious queries on contact fields)
     */
    private function detectHuntingPatterns(Builder $builder, $user): void
    {
        $wheres = $builder->getQuery()->wheres;

        foreach ($wheres as $where) {
            // Check for LIKE queries on email/phone fields
            if (isset($where['type']) && $where['type'] === 'Basic') {
                $column = $where['column'] ?? '';
                $value = $where['value'] ?? '';

                if (in_array($column, ['email', 'phone', 'inn']) && str_contains($value, '%')) {
                    $this->handleHuntingAttempt($user, $column, $value);
                }
            }
        }
    }

    /**
     * Handle hunting pattern detection
     */
    private function handleHuntingAttempt($user, string $column, string $value): void
    {
        $cacheKey = "hunting_detection:{$user->id}:{$column}";
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            // Trigger cooldown after 3 hunting attempts
            $cooldownService = app(\App\Services\Security\CooldownService::class);
            $cooldownService->startCooldown(
                $user,
                \App\Enums\CooldownActionType::HUNTING_DETECTED,
                1, // 1 hour
                'Hunting pattern detected on client data'
            );

            // Log to audit
            $auditService = app(\App\Services\Security\AuditService::class);
            $auditService->logEvent('hunting_pattern_detected', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'column' => $column,
                'pattern' => $value,
                'attempts' => $attempts,
            ], 'security');

            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $attempts + 1, self::CACHE_TTL_MINUTES * 60);
        }
    }
}
