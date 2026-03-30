<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WashBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'auto_wash_bookings';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'vehicle_id',
            'service_name',
            'price_kopecks',
            'status',
            'scheduled_at',
            'duration_minutes',
            'correlation_id',
        ];

        protected $casts = [
            'price_kopecks' => 'integer',
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];

        /**
         * КАНОН 2026: Automatic ID & Tenant Scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (WashBooking $booking) {
                $booking->uuid = $booking->uuid ?? (string) Str::uuid();
                $booking->tenant_id = $booking->tenant_id ?? (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('auto_wash_bookings.tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Связь с транспортным средством.
         */
        public function vehicle(): BelongsTo
        {
            return $this->belongsTo(Vehicle::class, 'vehicle_id');
        }

        /**
         * Время окончания мойки.
         */
        public function getEstimatedEndAt(): \Illuminate\Support\Carbon
        {
            return $this->scheduled_at->addMinutes($this->duration_minutes);
        }
}
