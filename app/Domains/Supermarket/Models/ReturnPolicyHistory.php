<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReturnPolicyHistory - Модель истории изменений политики возврата.
 *
 * Хранит полный аудит-трейл всех изменений политик возврата:
 * - Кто изменил
 * - Что было до
 * - Что стало после
 * - Причина изменения
 * - Correlation ID для трассировки
 *
 * @property int $id
 * @property int $return_policy_id ID политики
 * @property int|null $changed_by ID пользователя, изменившего политику
 * @property array|null $old_values Старые значения
 * @property array|null $new_values Новые значения
 * @property string $change_type Тип изменения (create|update|delete|restore)
 * @property string|null $change_reason Причина изменения
 * @property string|null $correlation_id Correlation ID для трассировки
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class ReturnPolicyHistory extends Model
{
    protected $table = 'return_policy_histories';

    protected $fillable = [
        'return_policy_id',
        'changed_by',
        'old_values',
        'new_values',
        'change_type',
        'change_reason',
        'correlation_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Политика возврата.
     */
    public function returnPolicy(): BelongsTo
    {
        return $this->belongsTo(ReturnPolicy::class);
    }

    /**
     * Пользователь, изменивший политику.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }

    /**
     * Получить список изменённых полей.
     */
    public function getChangedFields(): array
    {
        if ($this->change_type === 'create') {
            return array_keys($this->new_values ?? []);
        }

        if ($this->change_type === 'delete') {
            return array_keys($this->old_values ?? []);
        }

        if ($this->old_values && $this->new_values) {
            return array_keys(array_diff_assoc($this->new_values, $this->old_values));
        }

        return [];
    }

    /**
     * Получить читаемое описание изменений.
     */
    public function getChangeDescription(): string
    {
        $fieldLabels = [
            'vertical' => 'Вертикаль',
            'sub_vertical' => 'Под-вертикаль',
            'customer_type' => 'Тип клиента',
            'max_days' => 'Макс. дней',
            'allowed_reasons' => 'Разрешённые причины',
            'cold_chain_only_defect' => 'Только брак для хол. цепи',
            'requires_photo' => 'Требуется фото',
            'requires_temperature' => 'Требуется температура',
            'requires_receipt' => 'Требуется чек',
            'max_refund_percent' => 'Макс. % возврата',
            'max_refund_amount' => 'Макс. сумма возврата',
            'description' => 'Описание',
            'is_active' => 'Активность',
            'priority' => 'Приоритет',
            'valid_from' => 'Действует с',
            'valid_until' => 'Действует до',
        ];

        $changedFields = $this->getChangedFields();
        $descriptions = [];

        foreach ($changedFields as $field) {
            $label = $fieldLabels[$field] ?? $field;
            
            if ($this->change_type === 'create') {
                $descriptions[] = "{$label}: {$this->formatValue($this->new_values[$field] ?? null)}";
            } elseif ($this->change_type === 'delete') {
                $descriptions[] = "{$label}: {$this->formatValue($this->old_values[$field] ?? null)}";
            } else {
                $old = $this->formatValue($this->old_values[$field] ?? null);
                $new = $this->formatValue($this->new_values[$field] ?? null);
                $descriptions[] = "{$label}: {$old} → {$new}";
            }
        }

        return implode(', ', $descriptions);
    }

    /**
     * Форматировать значение для отображения.
     */
    private function formatValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'да' : 'нет';
        }

        if (is_array($value)) {
            return '[' . implode(', ', $value) . ']';
        }

        return (string) $value;
    }

    /**
     * Scope для типа изменения.
     */
    public function scopeWithChangeType($query, string $changeType)
    {
        return $query->where('change_type', $changeType);
    }

    /**
     * Scope для пользователя.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * Scope для периода.
     */
    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
