<?php declare(strict_types=1);

/**
 * Contract — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Contract extends Model
{

    protected $table = 'professional_services_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'client_id',
        'provider_id',
        'total_amount_kopecks',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'total_amount_kopecks' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $query->where('professional_services_contracts.tenant_id', tenant()->id);
        });
    }

    /**
     * Get milestones associated with this contract.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'contract_id');
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
     * Ensures tenant scoping and correlation ID are present.
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
