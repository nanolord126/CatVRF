<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Протокол сессии.
 */
final class PsychologicalSession extends Model
{
    protected $table = 'psy_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'booking_id',
        'started_at',
        'ended_at',
        'therapist_notes',
        'homework',
        'emotional_state',
        'video_link',
        'correlation_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'emotional_state' => 'json',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(PsychologicalBooking::class, 'booking_id');
    }
}
