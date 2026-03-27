<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Бронирование сессии.
 */
final class PsychologicalBooking extends Model
{
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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
            $model->tenant_id = auth()->user()->tenant_id ?? 0;
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
