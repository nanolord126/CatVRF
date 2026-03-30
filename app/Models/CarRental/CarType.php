<?php declare(strict_types=1);

namespace App\Models\CarRental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarType extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'car_types';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'daily_price_base',
            'seats',
            'baggage_capacity',
            'features',
            'correlation_id',
        ];

        /**
         * Casting logic for nested JSON structures.
         */
        protected $casts = [
            'features' => 'json',
            'daily_price_base' => 'integer',
            'seats' => 'integer',
            'baggage_capacity' => 'integer',
            'uuid' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        /**
         * Boot logic for tenant-aware scoping.
         */
        protected static function booted(): void
        {
            // 1. Force Tenant Scoping via global scope
            static::addGlobalScope('tenant', function (Builder $builder) {
                $tenantId = tenant()->id ?? config('multitenancy.default_tenant_id');
                if ($tenantId) {
                    $builder->where('car_types.tenant_id', $tenantId);
                }
            });

            // 2. Automatic UUID generation and correlation assignment
            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->correlation_id)) {
                    $model->correlation_id = (string) Str::uuid();
                }
                if (empty($model->tenant_id)) {
                    $model->tenant_id = tenant()->id ?? 1;
                }
            });
        }

        /**
         * Relationship: Vehicles belonging to this classification.
         */
        public function cars(): HasMany
        {
            return $this->hasMany(Car::class, 'car_type_id');
        }

        /**
         * Relationship: Associated bookings via vehicles of this type.
         */
        public function bookings(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
        {
            return $this->hasManyThrough(Booking::class, Car::class, 'car_type_id', 'car_id');
        }

        /**
         * Formatted string for UI display.
         */
        public function getCapacityLabelAttribute(): string
        {
            return "{$this->seats} seats, {$this->baggage_capacity} bags";
        }

        /**
         * Retrieve base price per day formatted as human-readable string.
         */
        public function getFormattedPriceAttribute(): string
        {
            return number_format($this->daily_price_base / 100, 2, '.', ' ') . ' ₽';
        }

        /**
         * Correlation Tracking implementation.
         */
        public function getActiveTraceId(): string
        {
            return (string) ($this->correlation_id ?? 'root-trace-id');
        }
}
