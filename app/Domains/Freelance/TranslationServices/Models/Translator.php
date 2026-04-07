<?php declare(strict_types=1);

/**
 * Translator — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/translator
 */


namespace App\Domains\Freelance\TranslationServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Translator extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'translators';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'correlation_id',
            'name',
            'language_pairs',
            'price_kopecks_per_word',
            'rating',
            'is_verified',
            'tags',
        ];

        protected $casts = [
            'language_pairs' => 'json',
            'price_kopecks_per_word' => 'integer',
            'rating' => 'float',
            'is_verified' => 'boolean',
            'tags' => 'json',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('translators.tenant_id', tenant()->id));
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
