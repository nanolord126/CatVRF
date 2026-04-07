<?php declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class ConsultingSession extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'consulting_sessions';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consultant_id',
            'consulting_service_id',
            'client_id',
            'scheduled_at',
            'started_at',
            'ended_at',
            'duration_minutes',
            'price',
            'status', // 'pending', 'confirmed', 'completed', 'cancelled'
            'payment_status', // 'pending', 'paid', 'refunded'
            'meeting_url',
            'session_notes',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'consultant_id' => 'integer',
            'consulting_service_id' => 'integer',
            'client_id' => 'integer',
            'tags' => 'json',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        protected $hidden = [
            'deleted_at',
        ];

    /**
     * Boot logic for multi-tenancy and consistent UUID generation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id)) {
                $model->tenant_id = tenant()->id ?? 0;
            }
        });

        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

        /**
         * Relationships.
         */
        public function consultant(): BelongsTo
        {
            return $this->belongsTo(Consultant::class, 'consultant_id');
        }

        public function service(): BelongsTo
        {
            return $this->belongsTo(ConsultingService::class, 'consulting_service_id');
        }

        public function client(): BelongsTo
        {
            // Assuming App\Models\User exists
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        /**
         * Scopes.
         */
        public function scopeCompleted(Builder $query): Builder
        {
            return $query->where('status', 'completed');
        }

        public function scopeScheduledForToday(Builder $query): Builder
        {
            return $query->whereDate('scheduled_at', now()->today());
        }

        /**
         * Domain Methods.
         */
        public function isCompleted(): bool
        {
            return $this->status === 'completed';
        }

        public function isPaid(): bool
        {
            return $this->payment_status === 'paid';
        }

        public function isOverdue(): bool
        {
            return $this->status !== 'completed' && $this->scheduled_at->isPast();
        }

        public function canStart(): bool
        {
            return $this->status === 'confirmed' && $this->scheduled_at->diffInMinutes(now()) <= 15;
        }

        public function getFormattedPrice(): string
        {
            return number_format($this->price / 100, 2) . ' RUB';
        }

        public function getSessionDuration(): string
        {
            if ($this->duration_minutes === 0 || $this->duration_minutes === null) return 'Not yet recorded';
            return "{$this->duration_minutes} minutes";
        }

        public function logSessionStart(): void
        {
            $this->update([
               'started_at' => now(),
               'status' => 'confirmed',
               'correlation_id' => (string) Str::uuid(),
            ]);
        }

        public function logSessionEnd(int $actualDuration): void
        {
            $this->update([
               'ended_at' => now(),
               'duration_minutes' => $actualDuration,
               'status' => 'completed',
            ]);
        }
}
