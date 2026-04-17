<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Courier extends Model
{


        protected $table = 'couriers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'vehicle_id',
            'status',
            'current_location',
            'rating',
            'commission_percent',
            'tags',
            'correlation_id'
        ];

        protected $hidden = [
            'password',
            'token',
            'secret',
        ];

        protected $casts = [
            'uuid' => 'string',
            'status' => 'string',
            'rating' => 'float',
            'commission_percent' => 'integer',
            'tags' => 'json',
            'current_location' => 'object',
        ];

        /**
         * Is courier online and ready for new tasks.
         */
        public function isAvailable(): bool
        {
            return $this->status === 'online' && !$this->deleted_at;
        }

        /**
         * Get current status color for UI.
         */
        public function getStatusColor(): string
        {
            return match ($this->status) {
                'busy' => 'warning',
                default => 'gray',
            };
        }

        /**
         * Глобальная изоляция по tenant_id
         */
        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        // --- RELATIONS ---

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function vehicle(): BelongsTo
        {
            return $this->belongsTo(Vehicle::class, 'vehicle_id');
        }

        public function deliveryOrders(): HasMany
        {
            return $this->hasMany(DeliveryOrder::class, 'courier_id');
        }

        /**
         * Related routes for the courier via orders.
         */
        public function routes(): HasMany
        {
            return $this->hasMany(Route::class, 'courier_id');
        }
}
