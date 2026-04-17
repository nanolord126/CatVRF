<?php declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

        public function booking(): BelongsTo
        {
            return $this->belongsTo(PsychologicalBooking::class, 'booking_id');
        }
}
