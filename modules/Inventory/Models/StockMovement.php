<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель движения запаса товара.
 * Согласно КАНОН 2026: audit логирование всех движений, tenant scoping, correlation_id.
 *
 * @property int $id
 * @property int $product_id
 * @property int $tenant_id
 * @property string $type (in, out, adjust, reserve, release, correction)
 * @property int $quantity Количество (может быть отрицательным)
 * @property string|null $reason Причина движения
 * @property string|null $source_type (order, appointment, manual, import, refund)
 * @property int|null $source_id ID источника
 * @property int|null $user_id Пользователь, совершивший действие
 * @property string|null $reference_type (Order, Appointment, etc.)
 * @property int|null $reference_id ID ссылки
 * @property bool $is_approved Одобрено ли движение
 * @property string $status (pending, approved, rejected)
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class StockMovement extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_stock_movements';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'type',
        'quantity',
        'reason',
        'source_type',
        'source_id',
        'user_id',
        'reference_type',
        'reference_id',
        'is_approved',
        'status',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Типы движений.
     */
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUST = 'adjust';
    public const TYPE_RESERVE = 'reserve';
    public const TYPE_RELEASE = 'release';
    public const TYPE_CORRECTION = 'correction';

    /**
     * Статусы движений.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить товар.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product::class);
    }

    /**
     * Получить пользователя, совершившего движение.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Проверить, поступление ли это.
     */
    public function isInbound(): bool
    {
        return $this->quantity > 0 && in_array($this->type, [self::TYPE_IN, self::TYPE_ADJUST, self::TYPE_RELEASE]);
    }

    /**
     * Проверить, отпуск ли это.
     */
    public function isOutbound(): bool
    {
        return $this->quantity < 0 && in_array($this->type, [self::TYPE_OUT, self::TYPE_RESERVE]);
    }

    /**
     * Одобрить движение.
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'is_approved' => true,
            'status' => self::STATUS_APPROVED,
            'metadata' => array_merge($this->metadata ?? [], [
                'approved_by' => $approvedBy,
                'approved_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Отклонить движение.
     */
    public function reject(int $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'metadata' => array_merge($this->metadata ?? [], [
                'rejected_by' => $rejectedBy,
                'rejected_at' => now()->toIso8601String(),
                'rejection_reason' => $reason,
            ]),
        ]);
    }
}
