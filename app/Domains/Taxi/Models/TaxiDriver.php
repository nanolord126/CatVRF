<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

final class TaxiDriver extends Model
{

    use HasUuids, SoftDeletes;

        protected $table = 'taxi_drivers';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'user_id',
            'license_number',
            'rating',
            'completed_rides',
            'current_location',
            'is_active',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $casts = [
            'current_location' => 'json',
            'tags' => 'collection',
            'metadata' => 'json',
            'is_active' => 'boolean',
            'rating' => 'float',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant()?->id ?? 0));
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'user_id');
        }

        public function vehicles(): HasMany
        {
            return $this->hasMany(TaxiVehicle::class, 'driver_id');
        }

        public function rides(): HasMany
        {
            return $this->hasMany(TaxiRide::class, 'driver_id');
        }

        /**
         * Проверить, доступен ли водитель для заказа.
         */
        public function isAvailable(): bool
        {
            return $this->is_active && $this->rating >= 4.0;
        }

        /**
         * Рассчитать средний рейтинг за последние N поездок.
         */
        public function calculateRecentRating(int $lastRides = 50): float
        {
            $recentRides = $this->rides()
                ->where('status', TaxiRide::STATUS_COMPLETED)
                ->latest()
                ->take($lastRides)
                ->get();

            if ($recentRides->isEmpty()) {
                return $this->rating;
            }

            return $recentRides->avg('rating') ?? $this->rating;
        }

        /**
         * Получить общее количество заработка.
         */
        public function getTotalEarnings(): int
        {
            return $this->rides()
                ->where('status', TaxiRide::STATUS_COMPLETED)
                ->get()
                ->sum(function ($ride) {
                    return $ride->calculateDriverEarnings();
                });
        }

        /**
         * Проверить, имеет ли водитель активную поездку.
         */
        public function hasActiveRide(): bool
        {
            return $this->rides()
                ->whereIn('status', [TaxiRide::STATUS_ACCEPTED, TaxiRide::STATUS_STARTED])
                ->exists();
        }

        /**
         * Получить текущее местоположение.
         */
        public function getCurrentLocation(): ?array
        {
            return is_array($this->current_location) ? $this->current_location : null;
        }

        /**
         * Обновить местоположение водителя.
         */
        public function updateLocation(float $lat, float $lng): void
        {
            $this->update([
                'current_location' => ['lat' => $lat, 'lng' => $lng],
            ]);
        }

        /**
         * Рассчитать процент завершенных поездок.
         */
        public function getCompletionRate(): float
        {
            if ($this->completed_rides === 0) {
                return 0.0;
            }

            $totalRides = $this->rides()->count();
            if ($totalRides === 0) {
                return 0.0;
            }

            return ($this->completed_rides / $totalRides) * 100;
        }
}
