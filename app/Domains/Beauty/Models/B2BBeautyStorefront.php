<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * B2BBeautyStorefront — корпоративная витрина услуг бьюти-вертикали.
 * wholesale_discount и min_order_amount — в копейках (integer).
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $tenant_id
 * @property int    $business_group_id
 * @property string $company_name
 * @property string $inn
 * @property bool   $is_verified
 * @property bool   $is_active
 * @property int    $wholesale_discount  (копейки)
 * @property int    $min_order_amount    (копейки)
 */
final class B2BBeautyStorefront extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_beauty_storefronts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'company_name',
        'inn',
        'description',
        'wholesale_packages',
        'wholesale_discount',
        'min_order_amount',
        'is_verified',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'wholesale_packages' => 'json',
        'tags'               => 'json',
        'is_verified'        => 'boolean',
        'is_active'          => 'boolean',
        'wholesale_discount' => 'integer',
        'min_order_amount'   => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', static function ($query): void {
            if ($tenantId = tenant()->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function b2bOrders(): HasMany
    {
        return $this->hasMany(B2BBeautyOrder::class, 'b2b_beauty_storefront_id');
    }

    public function isVerifiedAndActive(): bool
    {
        return $this->is_verified && $this->is_active;
    }

    public function canAcceptOrder(int $orderAmountKopecks): bool
    {
        return $this->isVerifiedAndActive()
            && $orderAmountKopecks >= $this->min_order_amount;
    }

    public function getWholesaleDiscountPercent(): float
    {
        return $this->wholesale_discount / 100;
    }
}