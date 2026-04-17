<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

final class Vehicle extends Model
{


        protected $table = 'taxi_vehicles';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'driver_id',
            'brand',
            'model',
            'plate_number',
            'color',
            'year',
            'class',
            'documents',
            'status',
            'correlation_id',
            'tags'
        ];

        protected $casts = [
            'documents' => 'json',
            'tags' => 'json',
            'year' => 'integer',
            'tenant_id' => 'integer',
            'driver_id' => 'integer'
        ];

        /**
         * Глобальный скоупинг тенанта.
         */
        protected static function booted(): void
        {
            static::creating(function (Vehicle $vehicle) {
                $vehicle->uuid = $vehicle->uuid ?? (string) Str::uuid();
                $vehicle->tenant_id = $vehicle->tenant_id ?? (tenant()->id ?? 1);
                $vehicle->correlation_id = $vehicle->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
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
        public function driver(): BelongsTo
        {
            return $this->belongsTo(Driver::class);
        }

        /**
         * Проверка на подписку (бизнес-логика).
         */
        public function isAllowedForClass(string $requestedClass): bool
        {
            static $classes = [
                'economy' => 1,
                'comfort' => 2,
                'business' => 3,
                'delivery' => 0
            ];

            return ($classes[$this->class] ?? 0) >= ($classes[$requestedClass] ?? 0);
        }

        /**
         * Проверить, доступен ли автомобиль.
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
            if (!is_array($this->documents) || empty($this->documents)) {
                return false;
            }

            $requiredDocs = ['insurance', 'registration', 'inspection'];
            foreach ($requiredDocs as $doc) {
                if (!isset($this->documents[$doc]) || !$this->documents[$doc]['valid'] ?? false) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Получить класс автомобиля для отображения.
         */
        public function getClassLabel(): string
        {
            $labels = [
                'economy' => 'Эконом',
                'comfort' => 'Комфорт',
                'business' => 'Бизнес',
                'delivery' => 'Доставка',
            ];

            return $labels[$this->class] ?? $this->class;
        }

        /**
         * Проверить, соответствует ли автомобиль требованиям класса.
         */
        public function meetsClassRequirements(string $requiredClass): bool
        {
            $requirements = [
                'economy' => ['min_year' => 2015],
                'comfort' => ['min_year' => 2018],
                'business' => ['min_year' => 2020],
            ];

            $req = $requirements[$requiredClass] ?? null;
            if ($req === null) {
                return true;
            }

            return $this->year >= ($req['min_year'] ?? 0);
        }
}
