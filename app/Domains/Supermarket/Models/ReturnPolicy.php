<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ReturnPolicy - Модель политики возврата для SubVertical.
 *
 * Определяет гибкие правила возврата в зависимости от:
 * - Вертикали (supermarket, restaurant, etc.)
 * - Под-вертикали (meat_shops, vegan_products, confectionery, etc.)
 * - Типа клиента (B2B или B2C)
 * - Временного периода (valid_from, valid_until)
 *
 * Поддерживает историю изменений через ReturnPolicyHistory.
 *
 * @property int $id
 * @property string $vertical Вертикаль
 * @property string|null $sub_vertical Под-вертикаль
 * @property string $customer_type Тип клиента (b2c|b2b)
 * @property int $max_days Максимальное количество дней на возврат
 * @property array|null $allowed_reasons Разрешённые причины возврата
 * @property bool $cold_chain_only_defect Возврат только при браке для холодной цепи
 * @property bool $requires_photo Обязательно фото доказательства
 * @property bool $requires_temperature Проверка температуры
 * @property bool $requires_receipt Обязателен чек
 * @property int $max_refund_percent Максимальный % возврата
 * @property int|null $max_refund_amount Максимальная сумма возврата (в копейках)
 * @property string|null $description Описание политики
 * @property bool $is_active Активна ли политика
 * @property int $priority Приоритет применения (выше = важнее)
 * @property \Illuminate\Support\Carbon|null $valid_from Дата начала действия
 * @property \Illuminate\Support\Carbon|null $valid_until Дата окончания действия
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class ReturnPolicy extends Model
{
    use SoftDeletes;
    use WithAuditLogging;

    protected $table = 'return_policies';

    protected $fillable = [
        'vertical',
        'sub_vertical',
        'customer_type',
        'max_days',
        'allowed_reasons',
        'cold_chain_only_defect',
        'requires_photo',
        'requires_temperature',
        'requires_receipt',
        'max_refund_percent',
        'max_refund_amount',
        'description',
        'is_active',
        'priority',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'max_days' => 'integer',
        'allowed_reasons' => 'array',
        'cold_chain_only_defect' => 'boolean',
        'requires_photo' => 'boolean',
        'requires_temperature' => 'boolean',
        'requires_receipt' => 'boolean',
        'max_refund_percent' => 'integer',
        'max_refund_amount' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * История изменений политики.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ReturnPolicyHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Пользователь, изменивший политику последним (через историю).
     */
    public function lastChangedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'id', 'changed_by')
            ->through(ReturnPolicyHistory::class);
    }

    /**
     * Проверить, активна ли политика сейчас.
     */
    public function isActiveNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Проверить, разрешена ли причина возврата.
     */
    public function isReasonAllowed(string $reason): bool
    {
        if (empty($this->allowed_reasons)) {
            return true; // если список пуст, разрешены все причины
        }

        return in_array($reason, $this->allowed_reasons);
    }

    /**
     * Проверить, превышен ли лимит суммы возврата.
     */
    public function isRefundAmountExceeded(int $amount): bool
    {
        if ($this->max_refund_amount === null) {
            return false;
        }

        return $amount > $this->max_refund_amount;
    }

    /**
     * Рассчитать максимальную сумму возврата на основе процента.
     */
    public function calculateMaxRefundAmount(int $originalAmount): int
    {
        return (int) ($originalAmount * ($this->max_refund_percent / 100));
    }

    /**
     * Получить описание политики для пользователя.
     */
    public function getUserDescription(): string
    {
        $parts = [];

        $parts[] = "Возврат возможен в течение {$this->max_days} дней.";

        if (!empty($this->allowed_reasons)) {
            $reasons = array_map(fn ($r) => match ($r) {
                'spoiled' => 'испорчено',
                'wrong_item' => 'не тот товар',
                'changed_mind' => 'передумал',
                'damaged' => 'повреждено',
                'expired' => 'просрочено',
                default => $r,
            }, $this->allowed_reasons);
            $parts[] = "Разрешенные причины: " . implode(', ', $reasons) . '.';
        }

        if ($this->cold_chain_only_defect) {
            $parts[] = "Для товаров с холодной цепью возврат только при браке.";
        }

        if ($this->requires_photo) {
            $parts[] = "Требуются фото доказательства.";
        }

        if ($this->requires_temperature) {
            $parts[] = "Требуется проверка температуры.";
        }

        if ($this->max_refund_percent < 100) {
            $parts[] = "Максимальный возврат: {$this->max_refund_percent}% от суммы.";
        }

        return implode(' ', $parts);
    }

    /**
     * Scope для активных политик.
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $now);
            });
    }

    /**
     * Scope для вертикали.
     */
    public function scopeForVertical($query, string $vertical)
    {
        return $query->where('vertical', $vertical);
    }

    /**
     * Scope для под-вертикали.
     */
    public function scopeForSubVertical($query, ?string $subVertical)
    {
        if ($subVertical === null) {
            return $query->whereNull('sub_vertical');
        }

        return $query->where('sub_vertical', $subVertical);
    }

    /**
     * Scope для типа клиента.
     */
    public function scopeForCustomerType($query, string $customerType)
    {
        return $query->where('customer_type', $customerType);
    }

    /**
     * Scope для сортировки по приоритету.
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Создать запись в истории изменений.
     */
    public function recordHistory(
        string $changeType,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $changedBy = null,
        ?string $reason = null,
        ?string $correlationId = null
    ): ReturnPolicyHistory {
        return $this->histories()->create([
            'changed_by' => $changedBy ?? auth()->id(),
            'old_values' => $oldValues,
            'new_values' => $newValues ?? $this->getAttributes(),
            'change_type' => $changeType,
            'change_reason' => $reason,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Boot метод для автоматического записи истории изменений.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->recordHistory('create', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getDirty();
            if (!empty($changes)) {
                $oldValues = [];
                $newValues = [];
                
                foreach ($changes as $key => $value) {
                    $oldValues[$key] = $model->getOriginal($key);
                    $newValues[$key] = $value;
                }
                
                $model->recordHistory('update', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            $model->recordHistory('delete', $model->getAttributes(), null);
        });
    }
}
