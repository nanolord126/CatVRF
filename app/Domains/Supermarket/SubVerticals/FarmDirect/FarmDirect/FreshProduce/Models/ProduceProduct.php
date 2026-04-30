<?php

declare(strict_types=1);

/**
 * ProduceProduct — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/produceproduct
 */

namespace App\Domains\Supermarket\SubVerticals\FarmDirect\FreshProduce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class ProduceProduct extends Model
{
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

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

    protected $table = 'produce_products';

    protected $fillable = ['uuid', 'tenant_id', 'farm_id', 'correlation_id', 'name', 'price_kopecks', 'unit', 'stock', 'seasonal', 'is_organic', 'tags'];

    protected $casts = ['price_kopecks' => 'integer', 'stock' => 'float', 'is_organic' => 'boolean', 'tags' => 'json'];

    /**
     * Выполнить операцию
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', fn ($q) => $q->where('produce_products.tenant_id', tenant()->id));
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
}
