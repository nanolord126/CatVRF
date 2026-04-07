<?php declare(strict_types=1);

/**
 * FashionWishlist — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionwishlist
 */


namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionWishlist extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'fashion_wishlists';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'product_id',
            'color',
            'size',
            'tags',
            'correlation_id',
        ];

        protected $casts = [
            'tags' => 'collection',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionProduct::class, 'product_id');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
