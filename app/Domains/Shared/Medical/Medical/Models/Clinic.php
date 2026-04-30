<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class Clinic extends Model
{
    use TenantScoped;

    protected $table = 'medical_clinics';

    /**
     * Поля, разрешенные для массового заполнения.
     * Обязательно: uuid, tenant_id, name, license_number, correlation_id.
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'license_number',
        'address',
        'geo_point',
        'status',
        'rating',
        'review_count',
        'contact_info',
        'working_hours',
        'metadata',
        'tags',
        'correlation_id',
    ];

    /**
     * Приведение типов для JSON полей.
     */
    protected $casts = [
        'geo_point' => 'array',
        'contact_info' => 'array',
        'working_hours' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Логирование активности модели для аудита.
     */
    /**
     * Отношение: Врачи клиники.
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class, 'clinic_id');
    }

    /**
     * Отношение: Услуги клиники.
     */
    public function services(): HasMany
    {
        return $this->hasMany(MedicalService::class, 'clinic_id');
    }

    /**
     * Отношение: Записи на прием в клинике.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'clinic_id');
    }

    /**
     * Отношение: Расходные материалы.
     */
    public function consumables(): HasMany
    {
        return $this->hasMany(Consumable::class, 'clinic_id');
    }

    /**
     * КАНОН: Глобальный Scope для изоляции по Tenant.
     */
    protected static function booted_disabled(): void
    {
        self::creating(function (Clinic $clinic) {
            $clinic->uuid = $clinic->uuid ?? (string) Str::uuid();
            $clinic->tenant_id = $clinic->tenant_id ?? (int) tenant()->id;
            $clinic->correlation_id = $clinic->correlation_id ?? (string) Str::uuid();
        });

        self::addGlobalScope('tenant_id', function ($builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
