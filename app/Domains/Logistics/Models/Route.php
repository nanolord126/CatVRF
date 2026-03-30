<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Route extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'logistics_routes';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'delivery_order_id',
            'courier_id',
            'vehicle_id',
            'path_coordinates', // Array of coordinate points [{"lat": 55.1, "lon": 37.2}, ...]
            'status', // proposed, active, completed, deviations
            'estimated_duration_minutes',
            'actual_duration_minutes',
            'estimated_distance_meters',
            'actual_distance_meters',
            'started_at',
            'finished_at',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'delivery_order_id' => 'integer',
            'courier_id' => 'integer',
            'vehicle_id' => 'integer',
            'path_coordinates' => 'array',
            'status' => 'string',
            'estimated_duration_minutes' => 'integer',
            'actual_duration_minutes' => 'integer',
            'estimated_distance_meters' => 'integer',
            'actual_distance_meters' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
            'tags' => 'array',
            'correlation_id' => 'string',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant') && tenant('id')) {
                    $model->tenant_id = (int) tenant('id');
                }
                if (empty($model->status)) {
                    $model->status = 'proposed';
                }
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        /**
         * Отношения
         */
        public function deliveryOrder(): BelongsTo
        {
            return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
        }

        public function courier(): BelongsTo
        {
            return $this->belongsTo(Courier::class, 'courier_id');
        }

        public function vehicle(): BelongsTo
        {
            return $this->belongsTo(Vehicle::class, 'vehicle_id');
        }

        /**
         * Бизнес-логика (2026 Production Ready)
         */
        public function start(string $correlationId): void
        {
            $this->update([
                'status' => 'active',
                'started_at' => now(),
                'correlation_id' => $correlationId,
            ]);
        }

        public function complete(int $actualDistance, string $correlationId): void
        {
            $now = now();
            $duration = $this->started_at ? $now->diffInMinutes($this->started_at) : 0;

            $this->update([
                'status' => 'completed',
                'finished_at' => $now,
                'actual_duration_minutes' => $duration,
                'actual_distance_meters' => $actualDistance,
                'correlation_id' => $correlationId,
            ]);
        }

        public function calculateEfficiency(): float
        {
            if (!$this->estimated_duration_minutes || !$this->actual_duration_minutes) {
                return 1.0;
            }

            return $this->estimated_duration_minutes / $this->actual_duration_minutes;
        }

        public function hasDeviations(): bool
        {
            if (!$this->estimated_distance_meters || !$this->actual_distance_meters) {
                return false;
            }

            // Отклонение более 20% считается подозрительным
            return $this->actual_distance_meters > ($this->estimated_distance_meters * 1.2);
        }

        public function getStatusLabel(): string
        {
            return match($this->status) {
                'proposed' => 'Предложенный',
                'active' => 'В процессе',
                'completed' => 'Завершенный',
                'deviations' => 'С отклонениями',
                default => 'Неизвестно',
            };
        }

        public function getStatusColor(): string
        {
            return match($this->status) {
                'proposed' => 'gray',
                'active' => 'blue',
                'completed' => 'success',
                'deviations' => 'warning',
                default => 'secondary',
            };
        }
}
