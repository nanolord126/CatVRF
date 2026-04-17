<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * VerticalOrder — модель заказа в вертикали VerticalName.
 *
 * Tenant-aware, привязан к VerticalItem.
 *
 * CANON 2026 — Layer 1: Models.
 * uuid, correlation_id, tags — обязательны.
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property int         $user_id
 * @property int         $vertical_item_id
 * @property string      $status
 * @property int         $quantity
 * @property int         $total_price_kopecks
 * @property bool        $is_b2b
 * @property array|null  $tags
 * @property array|null  $metadata
 * @property string|null $correlation_id
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class VerticalOrder extends Model
{

    protected $table = 'vertical_name_orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'vertical_item_id',
        'status',
        'quantity',
        'total_price_kopecks',
        'is_b2b',
        'tags',
        'metadata',
        'correlation_id',
        'paid_at',
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_b2b' => 'boolean',
        'quantity' => 'integer',
        'total_price_kopecks' => 'integer',
        'paid_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Инициализация — tenant scoping + авто-генерация uuid/correlation_id.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }

            if ($model->status === null) {
                $model->status = 'pending';
            }
        });
    }

    /**
     * Tenant, которому принадлежит заказ.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Tenant::class,
            'tenant_id',
        );
    }

    /**
     * Товар, к которому относится заказ.
     */
    public function verticalItem(): BelongsTo
    {
        return $this->belongsTo(
            VerticalItem::class,
            'vertical_item_id',
        );
    }

    /**
     * Пользователь-покупатель.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'user_id',
        );
    }

    /**
     * Business Group (если B2B-заказ).
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\BusinessGroup::class,
            'business_group_id',
        );
    }

    /**
     * Получить итог в рублях.
     */
    public function getTotalPriceRublesAttribute(): float
    {
        return round($this->total_price_kopecks / 100, 2);
    }

    /**
     * Заказ оплачен?
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->paid_at !== null;
    }

    /**
     * Scope для B2B-заказов.
     */
    public function scopeB2b($query): void
    {
        $query->where('is_b2b', true);
    }

    /**
     * Scope для B2C-заказов.
     */
    public function scopeB2c($query): void
    {
        $query->where('is_b2b', false);
    }
}
