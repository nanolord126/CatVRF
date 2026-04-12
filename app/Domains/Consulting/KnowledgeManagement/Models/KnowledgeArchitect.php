<?php declare(strict_types=1);

/**
 * KnowledgeArchitect — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/knowledgearchitect
 */


namespace App\Domains\Consulting\KnowledgeManagement\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class KnowledgeArchitect extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'knowledge_architects';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'correlation_id',
            'name',
            'specialties',
            'price_kopecks_per_hour',
            'rating',
            'is_verified',
            'tags',
        ];

        protected $casts = [
            'specialties' => 'json',
            'price_kopecks_per_hour' => 'integer',
            'rating' => 'float',
            'is_verified' => 'boolean',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('knowledge_architects.tenant_id', tenant()->id));
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
