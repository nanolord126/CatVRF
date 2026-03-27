<?php

declare(strict_types=1);

namespace App\Domains\Pet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: PET SERVICE MODEL
 * 
 * Модель ветеринарной услуги или услуги груминга.
 * Scoping: tenant_id.
 * Канон: 60+ строк, UUID, correlation_id, JSONB tags.
 */
final class PetService extends Model
{
    use HasFactory;

    protected $table = 'pet_services';

    /**
     * Поля, доступные для массового заполнения.
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'name',
        'category', // surgery, grooming, diagnostics, vaccination
        'duration_minutes',
        'price', // Цена в копейках
        'consumables_json',
        'requires_vaccination',
        'correlation_id',
        'tags',
    ];

    /**
     * Приведение типов.
     */
    protected $casts = [
        'consumables_json' => 'json',
        'tags' => 'json',
        'duration_minutes' => 'integer',
        'price' => 'integer',
        'requires_vaccination' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Инициализация модели.
     */
    protected static function booted(): void
    {
        // Global scope для изоляции тенантов
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        // Автогенерация UUID и correlation_id
        static::creating(function (PetService $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
            
            if (function_exists('tenant') && tenant()) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }

    /**
     * Клиника, предоставляющая данную услугу.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PetClinic::class, 'clinic_id');
    }

    /**
     * Записи на эту услугу.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(PetAppointment::class, 'service_id');
    }

    /**
     * Проверка: требует ли услуга обязательной вакцинации.
     */
    public function isVaccinationRequired(): bool
    {
        return $this->requires_vaccination;
    }

    /**
     * Получить цену в рублях.
     */
    public function getPriceInRubles(): float
    {
        return (float) ($this->price / 100);
    }

    /**
     * Получить форматированную цену.
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->getPriceInRubles(), 2, '.', ' ') . ' ₽';
    }

    /**
     * Расчет времени завершения услуги на основе времени начала.
     */
    public function calculateEndTime(\DateTimeInterface $startsAt): \DateTimeInterface
    {
        $end = clone $startsAt;
        return $end->modify('+' . $this->duration_minutes . ' minutes');
    }

    /**
     * Проверка специализации услуги.
     */
    public function isSurgery(): bool
    {
        return $this->category === 'surgery';
    }

    /**
     * Получить список необходимых расходников.
     */
    public function getConsumablesList(): array
    {
        return $this->consumables_json ?? [];
    }
}
