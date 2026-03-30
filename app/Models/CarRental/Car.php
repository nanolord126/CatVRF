<?php declare(strict_types=1);

namespace App\Models\CarRental;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Car extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'cars';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'rental_company_id',
            'car_type_id',
            'brand',
            'model',
            'plate_number',
            'mileage',
            'status',
            'attributes',
            'media',
            'correlation_id',
        ];

        /**
         * Property casting logic for features and photos.
         */
        protected $casts = [
            'attributes' => 'json',
            'media' => 'json',
            'mileage' => 'integer',
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
                    $builder->where('cars.tenant_id', $tenantId);
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
         * Relationship: The owner of this car.
         */
        public function company(): BelongsTo
        {
            return $this->belongsTo(RentalCompany::class, 'rental_company_id');
        }

        /**
         * Relationship: Vehicle class (Economy, Luxury, etc.).
         */
        public function type(): BelongsTo
        {
            return $this->belongsTo(CarType::class, 'car_type_id');
        }

        /**
         * Relationship: Current or past bookings for this unit.
         */
        public function bookings(): HasMany
        {
            return $this->hasMany(Booking::class, 'car_id');
        }

        /**
         * Logic check for availability.
         */
        public function isAvailable(): bool
        {
            return $this->status === 'available' && $this->deleted_at === null;
        }

        /**
         * Vehicle identifier formatting for UI.
         */
        public function getDisplayNameAttribute(): string
        {
            return "{$this->brand} {$this->model} ({$this->plate_number})";
        }

        /**
         * Retrieve base pricing through the associated type.
         */
        public function getBasePricePerDay(): int
        {
            return (int) ($this->type->daily_price_base ?? 0);
        }

        /**
         * Scope for filtering by capacity or type.
         */
        public function scopeFilterByCategory(Builder $query, string $categoryUuid): Builder
        {
            return $query->whereHas('type', function ($q) use ($categoryUuid) {
                $q->where('uuid', $categoryUuid);
            });
        }

        /**
         * Correlation Tracking implementation.
         */
        public function getActiveTraceId(): string
        {
            return (string) ($this->correlation_id ?? 'root-trace-id');
        }
}
