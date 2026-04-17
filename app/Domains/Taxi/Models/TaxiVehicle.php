<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TaxiVehicle extends Model
{

    use HasUuids, SoftDeletes;

        protected $table = 'taxi_vehicles';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'driver_id',
            'fleet_id',
            'brand',
            'model',
            'license_plate',
            'class',
            'status',
            'year',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $casts = [
            'tags' => 'collection',
            'metadata' => 'json',
            'year' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id ?? 0));
        }

        public function driver(): BelongsTo
        {
            return $this->belongsTo(TaxiDriver::class, 'driver_id');
        }

        public function fleet(): BelongsTo
        {
            return $this->belongsTo(TaxiFleet::class, 'fleet_id');
        }

        public function rides(): HasMany
        {
            return $this->hasMany(TaxiRide::class, 'vehicle_id');
        }

        /**
         * Проверить, доступно ли транспортное средство.
         */
        public function isAvailable(): bool
        {
            return $this->status === 'active';
        }

        /**
         * Рассчитать возраст автомобиля в годах.
         */
        public function getVehicleAge(): int
        {
            return (int) date('Y') - $this->year;
        }

        /**
         * Проверить, требует ли автомобиль техобслуживания.
         */
        public function requiresMaintenance(): bool
        {
            $age = $this->getVehicleAge();
            return $age > 5;
        }

        /**
         * Получить полное название автомобиля.
         */
        public function getFullName(): string
        {
            return "{$this->brand} {$this->model} ({$this->year})";
        }

        /**
         * Проверить валидность документов.
         */
        public function hasValidDocuments(): bool
        {
            if (!is_array($this->metadata) || !isset($this->metadata['documents'])) {
                return false;
            }

            $documents = $this->metadata['documents'];
            $requiredDocs = ['insurance', 'registration', 'inspection'];

            foreach ($requiredDocs as $doc) {
                if (!isset($documents[$doc]) || !$documents[$doc]['valid']) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Рассчитать пробег на основе количества поездок.
         */
        public function estimateMileage(): int
        {
            $rideCount = $this->rides()->where('status', TaxiRide::STATUS_COMPLETED)->count();
            return $rideCount * 15; // Средняя дистанция 15 км
        }
}
