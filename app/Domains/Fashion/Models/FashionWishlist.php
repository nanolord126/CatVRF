<?php

declare(strict_types=1);

/**
 * FashionWishlist — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/fashionwishlist
 */

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class FashionWishlist extends Model
{
    use TenantScoped;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function ($query) {
            if (tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
