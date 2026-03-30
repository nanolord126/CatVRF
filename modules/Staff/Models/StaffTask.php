<?php declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StaffTask extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;
    
        protected $table = 'staff_tasks';
    
        protected $fillable = [
            'tenant_id',
            'user_id',
            'uuid',
            'title',
            'description',
            'status',
            'priority',
            'taskable_id',
            'taskable_type',
            'due_date',
            'completed_at',
            'priority_order',
            'notes',
            'correlation_id',
            'metadata',
        ];
    
        protected $casts = [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'priority_order' => 'integer',
            'metadata' => 'json',
        ];
    
        protected $hidden = ['deleted_at'];
    
        /**
         * Статусы задачи.
         */
        public const STATUS_OPEN = 'open';
        public const STATUS_IN_PROGRESS = 'in_progress';
        public const STATUS_COMPLETED = 'completed';
        public const STATUS_CANCELLED = 'cancelled';
        public const STATUS_ON_HOLD = 'on_hold';
    
        /**
         * Приоритеты.
         */
        public const PRIORITY_LOW = 'low';
        public const PRIORITY_MEDIUM = 'medium';
        public const PRIORITY_HIGH = 'high';
        public const PRIORITY_CRITICAL = 'critical';
    
        /**
         * Приоритеты для сортировки.
         */
        private const PRIORITY_ORDER_MAP = [
            self::PRIORITY_LOW => 1,
            self::PRIORITY_MEDIUM => 2,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_CRITICAL => 4,
        ];
    
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
    
            static::creating(function ($task) {
                if ($task->priority) {
                    $task->priority_order = self::PRIORITY_ORDER_MAP[$task->priority] ?? 1;
                }
            });
        }
    
        /**
         * Получить пользователя.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
    
        /**
         * Получить связанную сущность (polymorphic).
         */
        public function taskable(): MorphTo
        {
            return $this->morphTo();
        }
    
        /**
         * Начать выполнение задачи.
         */
        public function startWorking(): void
        {
            $this->update(['status' => self::STATUS_IN_PROGRESS]);
        }
    
        /**
         * Завершить задачу.
         */
        public function complete(): void
        {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }
    
        /**
         * Отменить задачу.
         */
        public function cancel(): void
        {
            $this->update(['status' => self::STATUS_CANCELLED]);
        }
    
        /**
         * Поставить на паузу.
         */
        public function pause(): void
        {
            $this->update(['status' => self::STATUS_ON_HOLD]);
        }
    
        /**
         * Проверить, просрочена ли задача.
         */
        public function isOverdue(): bool
        {
            return $this->due_date && $this->due_date->isPast() && $this->status !== self::STATUS_COMPLETED;
        }
    
        /**
         * Получить дни до срока.
         */
        public function getDaysUntilDue(): int
        {
            if (!$this->due_date) {
                return 0;
            }
    
            return (int) now()->diffInDays($this->due_date);
        }
    
        /**
         * Проверить, завершена ли задача.
         */
        public function isCompleted(): bool
        {
            return $this->status === self::STATUS_COMPLETED;
        }
    
        /**
         * Проверить, в процессе ли выполнения.
         */
        public function isInProgress(): bool
        {
            return $this->status === self::STATUS_IN_PROGRESS;
        }
}
