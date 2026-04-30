<?php

declare(strict_types=1);

namespace App\Domains\VerticalName\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;

/**
 * VerticalOrder — модель заказа в вертикали VerticalName.
 *
 * Tenant-aware, привязан к VerticalItem.
 *
 * CANON 2026 — Layer 1: Models.
 * uuid, correlation_id, tags — обязательны.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int $user_id
 * @property int $vertical_item_id
 * @property string $status
 * @property int $quantity
 * @property int $total_price_kopecks
 * @property bool $is_b2b
 * @property array|null $tags
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $deleted_at
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
     * Tenant, которому принадлежит заказ.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
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
            User::class,
            'user_id',
        );
    }

    /**
     * Business Group (если B2B-заказ).
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(
            BusinessGroup::class,
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

    /**
     * Инициализация — tenant scoping + авто-генерация uuid/correlation_id.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant_scoping', static function ($builder): void {
            if (function_exists('tenant') && tenant() !== null) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(static function (self $model): void {
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
}
