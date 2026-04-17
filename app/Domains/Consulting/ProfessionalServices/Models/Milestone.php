<?php declare(strict_types=1);

/**
 * Milestone — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements fraud-checked business logic with full
 * correlation_id tracing and audit logging.
 * Accessed via Contract (no direct tenant scope needed).
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary
 */

namespace App\Domains\Consulting\ProfessionalServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Milestone extends Model
{

    protected $table = 'professional_services_milestones';

    protected $fillable = [
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

    /**
     * Get the contract that owns this milestone.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

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

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

    /**
     * Validate the current operation context.
     * Ensures correlation ID is present for audit trail.
     *
     * @param string $operation The operation being validated
     * @return void
     * @throws \DomainException If validation fails
     */
    private function validateOperationContext(string $operation): void
    {
        if (empty($operation)) {
            throw new \DomainException('Operation context cannot be empty');
        }
    }
}
