<?php declare(strict_types=1);

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Support\Str;

final class LegalConsultation extends Model
{

        protected $table = 'legal_consultations';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'lawyer_id',
            'client_id',
            'scheduled_at',
            'duration_minutes',
            'price',
            'status',
            'type',
            'summary',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'status' => 'string', // pending, confirmed, completed, cancelled
            'type' => 'string', // online, offline
            'summary' => 'string', // Encrypted if necessary
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant_id', function (Builder $builder) {
                if (function_exists('tenant')) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function lawyer(): BelongsTo
        {
            return $this->belongsTo(Lawyer::class, 'lawyer_id');
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(User::class, 'client_id');
        }

        public function contracts(): HasMany
        {
            return $this->hasMany(LegalContract::class, 'consultation_id');
        }

        public function scopeScheduledForToday(Builder $query): Builder
        {
            return $query->whereDate('scheduled_at', now());
        }

        public function scopeByStatus(Builder $query, string $status): Builder
        {
            return $query->where('status', $status);
        }

        public function isCompleted(): bool
        {
            return $this->status === 'completed';
        }

        public function isOnline(): bool
        {
            return $this->type === 'online';
        }

        public function canClientCancel(): bool
        {
            return $this->scheduled_at->isAfter(now()->addHours(24)) && $this->status !== 'completed';
        }

        public function getEstimatedEndTime(): \Carbon\Carbon
        {
            return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
        }
}
