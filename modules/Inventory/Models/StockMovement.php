<?php declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StockMovement extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
