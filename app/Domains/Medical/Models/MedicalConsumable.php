<?php

declare(strict_types=1);

namespace App\Domains\Medical\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Builder;

/**
 * КАНОН 2026: Модель Медицинского Расходника (Medical Consumable).
 * Слой 2: Доменные Модели.
 */
final class MedicalConsumable extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'medical_consumables';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'service_id',
        'name',
        'sku',
        'category',
        'stock_quantity',
        'min_threshold',
        'unit', // шт, мл, гр, уп
        'price_per_unit',
        'metadata',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'min_threshold' => 'integer',
        'price_per_unit' => 'integer',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    /**
     * КАНОН: Global Scopes и События модели.
     */
    protected static function booted(): void
    {
        static::creating(function (MedicalConsumable $consumable) {
            $consumable->uuid = $consumable->uuid ?? (string)Str::uuid();
            $consumable->tenant_id = $consumable->tenant_id ?? (int)tenant()->id;
            $consumable->correlation_id = $consumable->correlation_id ?? (string)Str::uuid();
        });

        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Настройка логов для аудита.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'stock_quantity', 'min_threshold'])
            ->logOnlyDirty()
            ->useLogName('medical_consumable_audit');
    }

    /**
     * Отношение: Клиника.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    /**
     * Отношение: Услуга (если расходник привязан к одной услуге).
     */
    public function medicalService(): BelongsTo
    {
        return $this->belongsTo(MedicalService::class, 'service_id');
    }

    /**
     * Проверка: критический остаток.
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_threshold;
    }

    /**
     * Списание остатка.
     */
    public function decrementStock(int $amount, string $reason = 'appointment_usage'): void
    {
        if ($this->stock_quantity < $amount) {
            throw new \Exception("Insufficient stock for consumable: {$this->name}");
        }

        $this->decrement('stock_quantity', $amount);
        
        // Регистрируем в аудите через metadata если нужно
        $this->updateQuietly([
            'metadata' => array_merge($this->metadata ?? [], [
                'last_decrement' => [
                    'amount' => $amount,
                    'reason' => $reason,
                    'at' => now()->toIso8601String()
                ]
            ])
        ]);
    }
}
