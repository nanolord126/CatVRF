<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'logistics_delivery_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'courier_id',
            'customer_id',
            'source_order_id',
            'pickup_point',
            'dropoff_point',
            'pickup_address',
            'dropoff_address',
            'status',
            'base_price',
            'surge_multiplier',
            'total_price',
            'estimated_delivery_at',
            'actual_delivery_at',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'uuid' => 'string',
            'pickup_point' => 'array',
            'dropoff_point' => 'array',
            'pickup_address' => 'array',
            'dropoff_address' => 'array',
            'status' => 'string',
            'base_price' => 'integer',
            'surge_multiplier' => 'float',
            'total_price' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
            'estimated_delivery_at' => 'datetime',
            'actual_delivery_at' => 'datetime',
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

                // Расчет итоговой цены, если не задана
                if (empty($model->total_price) && !empty($model->base_price)) {
                    $model->total_price = (int) ($model->base_price * ($model->surge_multiplier ?? 1.0));
                }
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function courier(): BelongsTo
        {
            return $this->belongsTo(Courier::class, 'courier_id');
        }

        public function customer(): BelongsTo
        {
            return $this->belongsTo(User::class, 'customer_id');
        }

        public function routes(): HasMany
        {
            return $this->hasMany(LogisticsRoute::class, 'delivery_order_id');
        }

        /**
         * Помощники статусов (2026 Production Ready)
         */
        public function isCompleted(): bool
        {
            return $this->status === 'completed';
        }

        public function isCancelled(): bool
        {
            return $this->status === 'cancelled';
        }

        public function isPending(): bool
        {
            return $this->status === 'pending';
        }

        public function getStatusLabel(): string
        {
            return match($this->status) {
                'pending' => 'Ожидает назначения',
                'assigned' => 'Курьер назначен',
                'at_pickup' => 'На точке забора',
                'in_transit' => 'В пути',
                'at_delivery' => 'На точке доставки',
                'completed' => 'Доставлено',
                'cancelled' => 'Отменено',
                'failed' => 'Ошибка доставки',
                default => 'Неизвестно',
            };
        }

        public function getStatusColor(): string
        {
            return match($this->status) {
                'pending' => 'gray',
                'assigned' => 'blue',
                'at_pickup' => 'orange',
                'in_transit' => 'info',
                'at_delivery' => 'primary',
                'completed' => 'success',
                'cancelled' => 'danger',
                'failed' => 'danger',
                default => 'secondary',
            };
        }

        /**
         * Мутации с аудитом (Audit Log Layer)
         */
        public function assignCourier(int $courierId, string $correlationId): void
        {
            \Illuminate\Support\Facades\DB::transaction(function() use ($courierId, $correlationId) {
                $this->update([
                    'courier_id' => $courierId,
                    'status' => 'assigned',
                    'correlation_id' => $correlationId,
                ]);

                \Illuminate\Support\Facades\Log::channel('audit')->info('Courier assigned to delivery order', [
                    'order_uuid' => $this->uuid,
                    'courier_id' => $courierId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function markAsDelivered(string $correlationId): void
        {
            \Illuminate\Support\Facades\DB::transaction(function() use ($correlationId) {
                $this->update([
                    'status' => 'completed',
                    'actual_delivery_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                \Illuminate\Support\Facades\Log::channel('audit')->info('Delivery order completed', [
                    'order_uuid' => $this->uuid,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
    }
            });
        }
}
