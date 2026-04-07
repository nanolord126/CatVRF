<?php declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

final class InventoryCheck extends Model
{
    use HasFactory, SoftDeletes;
    
        protected $table = 'inventory_checks';
    
        protected $fillable = [
            'tenant_id',
            'uuid',
            'check_date',
            'status',
            'user_id',
            'discrepancies_count',
            'discrepancy_percentage',
            'notes',
            'correlation_id',
            'metadata',
        ];
    
        protected $casts = [
            'check_date' => 'datetime',
            'discrepancies_count' => 'integer',
            'discrepancy_percentage' => 'float',
            'metadata' => 'json',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Статусы проверки.
         */
        public const STATUS_DRAFT = 'draft';
        public const STATUS_IN_PROGRESS = 'in_progress';
        public const STATUS_COMPLETED = 'completed';
        public const STATUS_REVIEWED = 'reviewed';
    
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
         * Получить все элементы проверки.
         */
        public function items(): HasMany
        {
            return $this->hasMany(\Modules\Inventory\Models\InventoryCheckItem::class, 'inventory_check_id');
        }
    
        /**
         * Получить пользователя, проводившего проверку.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    
        /**
         * Получить элементы с расхождениями.
         */
        public function getDiscrepancyItems()
        {
            return $this->items()
                ->whereRaw('expected_quantity != actual_quantity')
                ->get();
        }
    
        /**
         * Рассчитать процент расхождений.
         */
        public function calculateDiscrepancyPercentage(): float
        {
            $totalItems = $this->items()->count();
            if ($totalItems === 0) {
                return 0.0;
            }
    
            $discrepancies = $this->getDiscrepancyItems()->count();
            return ($discrepancies / $totalItems) * 100.0;
        }
    
        /**
         * Проверить, завершена ли проверка.
         */
        public function isCompleted(): bool
        {
            return $this->status === self::STATUS_COMPLETED;
        }
    
        /**
         * Помечить проверку как завершённую.
         */
        public function markAsCompleted(): void
        {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'discrepancies_count' => $this->getDiscrepancyItems()->count(),
                'discrepancy_percentage' => $this->calculateDiscrepancyPercentage(),
            ]);
        }
}
