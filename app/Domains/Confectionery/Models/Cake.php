<?php declare(strict_types=1);

/**
 * Cake — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cake
 */


namespace App\Domains\Confectionery\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Database\Factories\CakeFactory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Cake extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = 'cakes';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'price',
        'weight_grams',
        'layers',
        'flavour',
        'status',
        'tags',
    ];

        protected static function newFactory(): CakeFactory
        {
            return new CakeFactory();
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
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

}
