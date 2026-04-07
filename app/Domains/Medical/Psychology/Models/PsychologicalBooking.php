<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PsychologicalBooking extends Model
{

    use HasFactory;

    protected $table = 'psy_bookings';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'client_id',
            'psychologist_id',
            'service_id',
            'scheduled_at',
            'price_at_booking',
            'status',
            'payment_id',
            'client_notes',
            'correlation_id',
        ];

        protected $casts = [
            'scheduled_at' => 'datetime',
            'price_at_booking' => 'integer',
        ];

        protected static function booted_disabled(): void
        {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });

            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->correlation_id = (string) Str::uuid();
                $model->tenant_id = tenant()->id ?? 0;
            });
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        public function psychologist(): BelongsTo
        {
            return $this->belongsTo(Psychologist::class, 'psychologist_id');
        }

        public function service(): BelongsTo
        {
            return $this->belongsTo(PsychologicalService::class, 'service_id');
        }

        public function session(): HasOne
        {
            return $this->hasOne(PsychologicalSession::class, 'booking_id');
        }
}
