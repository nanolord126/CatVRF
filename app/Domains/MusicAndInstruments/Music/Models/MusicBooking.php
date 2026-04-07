<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Models;

use Illuminate\Http\Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicBooking extends Model
{
    use HasFactory;

        protected $table = 'music_bookings';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'correlation_id',
            'user_id',
            'bookable_type',
            'bookable_id',
            'starts_at',
            'ends_at',
            'total_price_cents',
            'status',
            'payment_status',
            'metadata',
            'tags',
        ];

        protected $casts = [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'total_price_cents' => 'integer',
            'metadata' => 'json',
            'tags' => 'array',
        ];

        /**
         * The "booted" method of the model.
         */
        protected static function booted_disabled(): void
        {
            static::creating(function ($model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

                // Tenant scoping
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()->id ?? 'null';
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('music_bookings.tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Get the parent bookable model (studio, lesson, instrument rental).
         */
        public function bookable(): \Illuminate\Database\Eloquent\Relations\MorphTo
        {
            return $this->morphTo();
        }

        /**
         * Get the user who made the booking.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        /**
         * Check if booking is confirmed.
         */
        public function isConfirmed(): bool
        {
            return $this->status === 'confirmed';
        }

        /**
         * Check if booking is paid.
         */
        public function isPaid(): bool
        {
            return $this->payment_status === 'paid';
        }
}
