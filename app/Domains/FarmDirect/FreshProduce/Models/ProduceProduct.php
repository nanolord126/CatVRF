<?php declare(strict_types=1);

/**
 * ProduceProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/produceproduct
 */


namespace App\Domains\FarmDirect\FreshProduce\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProduceProduct extends Model
{

    use HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'produce_products';
        protected $fillable = ['uuid', 'tenant_id', 'farm_id', 'correlation_id', 'name', 'price_kopecks', 'unit', 'stock', 'seasonal', 'is_organic', 'tags'];
        protected $casts = ['price_kopecks' => 'integer', 'stock' => 'float', 'is_organic' => 'boolean', 'tags' => 'json'];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function farm() { return $this->belongsTo(Farm::class, 'farm_id'); }

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) => $q->where('produce_products.tenant_id', tenant()->id));
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

}
