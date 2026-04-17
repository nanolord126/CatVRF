<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

final class Fleet extends Model
{


        protected $table = 'taxi_fleets';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'inn',
            'commission_rate',
            'settings',
            'status',
            'correlation_id',
            'tags'
        ];

        protected $casts = [
            'settings' => 'json',
            'tags' => 'json',
            'commission_rate' => 'float',
            'tenant_id' => 'integer'
        ];

        protected $hidden = ['settings'];

        /**
         * Глобальный скоупинг тенанта.
         */
        protected static function booted(): void
        {
            static::creating(function (Fleet $fleet) {
                $fleet->uuid = $fleet->uuid ?? (string) Str::uuid();
                $fleet->tenant_id = $fleet->tenant_id ?? (tenant()->id ?? 1);
                $fleet->status = $fleet->status ?? 'active';
                $fleet->correlation_id = $fleet->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
            });

            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Настройка логов активности.
         */
        

        /**
         * Отношения.
         */
        public function drivers(): HasMany
        {
            return $this->hasMany(Driver::class, 'fleet_id');
        }

        public function activeDrivers(): HasMany
        {
            return $this->hasMany(Driver::class, 'fleet_id')->where('is_active', true);
        }

        /**
         * Расчёт выплат.
         */
        public function calculateNetIncome(int $grossAmount): int
        {
            return (int) ($grossAmount * (1 - ($this->commission_rate / 100)));
        }

        /**
         * Рассчитать комиссию с суммы.
         */
        public function calculateCommission(int $amount): int
        {
            return (int) ($amount * ($this->commission_rate / 100));
        }

        /**
         * Получить количество активных водителей.
         */
        public function getActiveDriversCount(): int
        {
            return $this->activeDrivers()->count();
        }

        /**
         * Рассчитать общий доход автопарка.
         */
        public function calculateTotalRevenue(): int
        {
            $total = 0;
            foreach ($this->drivers as $driver) {
                $total += $driver->getTotalEarnings();
            }
            return $total;
        }

        /**
         * Рассчитать средний рейтинг водителей автопарка.
         */
        public function getAverageDriverRating(): float
        {
            $drivers = $this->drivers;
            if ($drivers->isEmpty()) {
                return 0.0;
            }

            return $drivers->avg('rating') ?? 0.0;
        }

        /**
         * Проверить, активен ли автопарк.
         */
        public function isActive(): bool
        {
            return $this->status === 'active';
        }

        /**
         * Получить статистику завершенных поездок.
         */
        public function getCompletedRidesStats(): array
        {
            $total = 0;
            foreach ($this->drivers as $driver) {
                $total += $driver->completed_rides;
            }

            return [
                'total' => $total,
                'by_driver' => $this->drivers->pluck('completed_rides', 'id'),
            ];
        }
}
