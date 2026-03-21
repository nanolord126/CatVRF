<?php

declare(strict_types=1);

namespace Modules\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель автомобиля в такси.
 * Согласно КАНОН 2026: информация об авто, техническое состояние, документы, статус.
 *
 * @property int $id
 * @property int $driver_id
 * @property int $tenant_id
 * @property string|null $uuid
 * @property string $license_plate Номерной знак
 * @property string $brand Марка автомобиля
 * @property string $model Модель
 * @property int $year Год выпуска
 * @property string $color Цвет
 * @property string $class Класс авто (economy, comfort, business, premium)
 * @property string $registration_number Номер свидетельства о регистрации
 * @property string $vin VIN номер
 * @property string|null $insurance_number Номер полиса страхования
 * @property \Carbon\Carbon|null $insurance_expires_at Дата истечения страховки
 * @property \Carbon\Carbon|null $inspection_expires_at Дата техосмотра
 * @property bool $is_verified Документы ли верифицированы
 * @property bool $is_active Активен ли автомобиль
 * @property string $status (available, maintenance, out_of_service, suspended)
 * @property int $ride_count Количество поездок на этом авто
 * @property int $total_earnings_kopeki Всего заработано на авто
 * @property float|null $mileage_km Пробег в км
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class TaxiVehicle extends Model
{
    use SoftDeletes;

    protected $table = 'taxi_vehicles';

    protected $fillable = [
        'driver_id',
        'tenant_id',
        'uuid',
        'license_plate',
        'brand',
        'model',
        'year',
        'color',
        'class',
        'registration_number',
        'vin',
        'insurance_number',
        'insurance_expires_at',
        'inspection_expires_at',
        'is_verified',
        'is_active',
        'status',
        'ride_count',
        'total_earnings_kopeki',
        'mileage_km',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'ride_count' => 'integer',
        'total_earnings_kopeki' => 'integer',
        'mileage_km' => 'float',
        'insurance_expires_at' => 'datetime',
        'inspection_expires_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Классы автомобилей.
     */
    public const CLASS_ECONOMY = 'economy';
    public const CLASS_COMFORT = 'comfort';
    public const CLASS_BUSINESS = 'business';
    public const CLASS_PREMIUM = 'premium';

    /**
     * Статусы автомобиля.
     */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_OUT_OF_SERVICE = 'out_of_service';
    public const STATUS_SUSPENDED = 'suspended';

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
     * Получить водителя.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(\Modules\Taxi\Models\TaxiDriver::class);
    }

    /**
     * Получить все поездки на этом авто.
     */
    public function rides(): HasMany
    {
        return $this->hasMany(\Modules\Taxi\Models\TaxiRide::class, 'vehicle_id');
    }

    /**
     * Получить заработки в рублях.
     */
    public function getEarningsInRubles(): float
    {
        return $this->total_earnings_kopeki / 100;
    }

    /**
     * Получить полное название автомобиля.
     */
    public function getFullName(): string
    {
        return "{$this->year} {$this->brand} {$this->model} ({$this->license_plate})";
    }

    /**
     * Проверить, доступен ли автомобиль для поездок.
     */
    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->status === self::STATUS_AVAILABLE
            && $this->is_verified
            && (!$this->insurance_expires_at || $this->insurance_expires_at->isFuture())
            && (!$this->inspection_expires_at || $this->inspection_expires_at->isFuture());
    }

    /**
     * Помечить как доступный.
     */
    public function markAsAvailable(): void
    {
        if ($this->isAvailable()) {
            $this->update(['status' => self::STATUS_AVAILABLE]);
        }
    }

    /**
     * Помечить на техническое обслуживание.
     */
    public function markAsMaintenance(): void
    {
        $this->update(['status' => self::STATUS_MAINTENANCE]);
    }

    /**
     * Помечить как выведённый из эксплуатации.
     */
    public function markAsOutOfService(): void
    {
        $this->update(['status' => self::STATUS_OUT_OF_SERVICE]);
    }

    /**
     * Обновить пробег.
     */
    public function updateMileage(float $newMileage): void
    {
        if ($newMileage >= $this->mileage_km) {
            $this->update(['mileage_km' => $newMileage]);
        }
    }

    /**
     * Добавить заработок.
     */
    public function addEarnings(int $amount): void
    {
        $this->increment('total_earnings_kopeki', $amount);
    }

    /**
     * Обновить страховку.
     */
    public function updateInsurance(string $number, \Carbon\Carbon $expiresAt): void
    {
        $this->update([
            'insurance_number' => $number,
            'insurance_expires_at' => $expiresAt,
        ]);
    }

    /**
     * Обновить техосмотр.
     */
    public function updateInspection(\Carbon\Carbon $expiresAt): void
    {
        $this->update(['inspection_expires_at' => $expiresAt]);
    }
}
