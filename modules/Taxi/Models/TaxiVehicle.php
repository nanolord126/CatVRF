<?php declare(strict_types=1);

namespace Modules\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TaxiVehicle extends Model
{
    use HasFactory;
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
