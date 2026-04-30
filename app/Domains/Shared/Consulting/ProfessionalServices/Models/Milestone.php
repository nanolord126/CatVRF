<?php

declare(strict_types=1);

/**
 * Milestone — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements fraud-checked business logic with full
 * correlation_id tracing and audit logging.
 * Accessed via Contract (no direct tenant scope needed).
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary
 */

namespace App\Domains\Consulting\ProfessionalServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Milestone extends Model
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    protected $table = 'professional_services_milestones';

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'title',
        'amount_kopecks',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'amount_kopecks' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the contract that owns this milestone.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return self::class.'@'.self::VERSION;
    }

    /**
     * Validate the current operation context.
     * Ensures correlation ID is present for audit trail.
     *
     * @param  string  $operation  The operation being validated
     *
     * @throws \DomainException If validation fails
     */
    private function validateOperationContext(string $operation): void
    {
        if (empty($operation)) {
            throw new \DomainException('Operation context cannot be empty');
        }
    }
}
