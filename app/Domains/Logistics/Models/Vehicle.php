<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Vehicle extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    use HasFactory;

    use SoftDeletes;

        protected $table = 'logistics_vehicles';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'courier_id', // Текущий закрепленный курьер
            'type', // car, bike, electric_scooter, truck, walking
            'brand',
            'model',
            'year',
            'license_plate',
            'color',
            'load_capacity_kg',
            'volume_m3',
            'battery_level', // Для электросамокатов/электрокаров
            'fuel_type', // electric, gasoline, diesel, none
            'is_active',
            'status', // active, maintenance, broken, retired
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'courier_id' => 'integer',
            'load_capacity_kg' => 'float',
            'volume_m3' => 'float',
            'battery_level' => 'integer',
            'is_active' => 'boolean',
            'status' => 'string',
            'metadata' => 'array',
            'tags' => 'array',
            'correlation_id' => 'string',
            'year' => 'integer',
        ];

        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()?->id) {
                    $model->tenant_id = (int) tenant()?->id;
                }
                if (empty($model->status)) {
                    $model->status = 'active';
                }
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()?->id) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }

        /**
         * Отношения
         */
        public function courier(): BelongsTo
        {
            return $this->belongsTo(Courier::class, 'courier_id');
        }

        public function routes(): HasMany
        {
            return $this->hasMany(LogisticsRoute::class, 'vehicle_id');
        }

        /**
         * Бизнес-логика (2026 Production Ready)
         */
        public function canTransport(float $weightKg, float $volumeM3 = 0): bool
        {
            if (!$this->is_active || $this->status !== 'active') {
                return false;
            }

            $canCarryWeight = $this->load_capacity_kg >= $weightKg;
            $canCarryVolume = $volumeM3 === 0 || $this->volume_m3 >= $volumeM3;

            return $canCarryWeight && $canCarryVolume;
        }

        public function markForMaintenance(string $reason, string $correlationId): void
        {
            $this->update([
                'status' => 'maintenance',
                'is_active' => false,
                'metadata' => array_merge($this->metadata ?? [], [
                    'maintenance_reason' => $reason,
                    'maintenance_started_at' => now()->toIso8601String(),
                ]),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->warning('Vehicle sent to maintenance', [
                'vehicle_uuid' => $this->uuid,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        }

        public function getTypeLabel(): string
        {
            return match($this->type) {
                'bike' => 'Велосипед',
                'electric_scooter' => 'Электросамокат',
                'truck' => 'Грузовик',
                'walking' => 'Пеший курьер',
                default => 'Другое',
            };
        }

        public function getStatusColor(): string
        {
            return match($this->status) {
                'maintenance' => 'warning',
                'broken' => 'danger',
                'retired' => 'gray',
                default => 'secondary',
            };
        }

        public function needsCharging(): bool
        {
            return in_array($this->type, ['electric_scooter', 'electric_car']) && $this->battery_level < 20;
        }
}
