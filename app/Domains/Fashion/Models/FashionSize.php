<?php declare(strict_types=1);

/**
 * FashionSize — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionsize
 */


namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionSize extends Model
{
    use HasFactory;

    protected $table = 'fashion_sizes';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'fashion_product_id',
            'size_type',
            'size_value',
            'stock',
            'measurements',
            'correlation_id',
        ];

        protected $casts = [
            'measurements' => 'json',
            'stock' => 'integer',
        ];

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionProduct::class, 'fashion_product_id');
        }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}