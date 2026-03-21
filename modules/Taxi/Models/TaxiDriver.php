<?php

declare(strict_types=1);

namespace Modules\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель водителя такси.
 * Согласно КАНОН 2026: профиль водителя, рейтинг, статус документов, earnings tracking.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string|null $uuid
 * @property string $full_name Полное имя
 * @property string $phone_number Номер телефона
 * @property string|null $license_number Номер водительского удостоверения
 * @property string|null $license_expires_at Дата истечения лицензии
 * @property bool $is_verified Верифицирован ли документы
 * @property bool $is_active Активен ли водитель
 * @property string $status (available, busy, offline, suspended, banned)
 * @property float $rating Рейтинг водителя (0-5)
 * @property int $ride_count Количество завершённых поездок
 * @property int $earnings_kopeki Общие заработки в копейках
 * @property float|null $current_latitude Текущая широта
 * @property float|null $current_longitude Текущая долгота
 * @property \Carbon\Carbon|null $last_location_update Время последнего обновления локации
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class TaxiDriver extends Model
{
    use SoftDeletes;

    protected $table = 'taxi_drivers';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'uuid',
        'full_name',
        'phone_number',
        'license_number',
        'license_expires_at',
        'is_verified',
        'is_active',
        'status',
        'rating',
        'ride_count',
        'earnings_kopeki',
        'current_latitude',
        'current_longitude',
        'last_location_update',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'float',
        'ride_count' => 'integer',
        'earnings_kopeki' => 'integer',
        'current_latitude' => 'float',
        'current_longitude' => 'float',
        'license_expires_at' => 'datetime',
        'last_location_update' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Статусы водителя.
     */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BUSY = 'busy';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED = 'banned';

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
     * Получить пользователя.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Получить все поездки водителя.
     */
    public function rides(): HasMany
    {
        return $this->hasMany(\Modules\Taxi\Models\TaxiRide::class, 'driver_id');
    }

    /**
     * Получить все транспортные средства водителя.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(\Modules\Taxi\Models\TaxiVehicle::class, 'driver_id');
    }

    /**
     * Получить заработки в рублях.
     */
    public function getEarningsInRubles(): float
    {
        return $this->earnings_kopeki / 100;
    }

    /**
     * Обновить текущее местоположение.
     */
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_update' => now(),
        ]);
    }

    /**
     * Проверить, доступен ли водитель для поездок.
     */
    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->status === self::STATUS_AVAILABLE
            && $this->is_verified
            && (!$this->license_expires_at || $this->license_expires_at->isFuture());
    }

    /**
     * Перейти в статус "занят".
     */
    public function markAsBusy(): void
    {
        $this->update(['status' => self::STATUS_BUSY]);
    }

    /**
     * Перейти в статус "offline".
     */
    public function markAsOffline(): void
    {
        $this->update(['status' => self::STATUS_OFFLINE]);
    }

    /**
     * Вернуться в статус "available".
     */
    public function markAsAvailable(): void
    {
        if ($this->isAvailable()) {
            $this->update(['status' => self::STATUS_AVAILABLE]);
        }
    }

    /**
     * Добавить заработок.
     */
    public function addEarnings(int $amount): void
    {
        $this->increment('earnings_kopeki', $amount);
    }
}
