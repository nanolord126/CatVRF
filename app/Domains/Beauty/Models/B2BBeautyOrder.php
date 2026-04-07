<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * B2BBeautyOrder — заказ корпоративного клиента бьюти-вертикали.
 * Все суммы хранятся в копейках (integer).
 *
 * @property int    $id
 * @property string $uuid
 * @property int    $tenant_id
 * @property int    $b2b_beauty_storefront_id
 * @property string $order_number
 * @property int    $total_amount        (копейки)
 * @property int    $commission_amount   (копейки)
 * @property int    $discount_amount     (копейки)
 * @property string $status              (pending|confirmed|completed|cancelled)
 * @property string $correlation_id
 */
final class B2BBeautyOrder extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_beauty_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'b2b_beauty_storefront_id',
        'order_number',
        'company_contact_person',
        'company_phone',
        'items_json',
        'total_amount',
        'commission_amount',
        'discount_amount',
        'status',
        'expected_delivery_at',
        'notes',
        'correlation_id',
        'tags',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'items_json'           => 'json',
        'tags'                 => 'json',
        'total_amount'         => 'integer',
        'commission_amount'    => 'integer',
        'discount_amount'      => 'integer',
        'expected_delivery_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->order_number)) {
                $model->order_number = 'B2B-' . strtoupper(substr((string) Str::uuid(), 0, 8));
            }
        });

        static::addGlobalScope('tenant', static function ($query): void {
            if ($tenantId = tenant()->id) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    public function storefront(): BelongsTo
    {
        return $this->belongsTo(B2BBeautyStorefront::class, 'b2b_beauty_storefront_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getNetPayoutKopecks(): int
    {
        return $this->total_amount - $this->commission_amount - $this->discount_amount;
    }
}