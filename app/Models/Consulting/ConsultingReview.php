<?php declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class ConsultingReview extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'consulting_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consulting_session_id',
            'consultant_id',
            'client_id',
            'rating', // 1-100 scale (stored as integer)
            'comment',
            'is_verified',
            'response',
            'responded_at',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'consulting_session_id' => 'integer',
            'consultant_id' => 'integer',
            'client_id' => 'integer',
            'tags' => 'json',
            'rating' => 'integer',
            'is_verified' => 'boolean',
            'responded_at' => 'datetime',
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
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
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
        public function session(): BelongsTo
        {
            return $this->belongsTo(ConsultingSession::class, 'consulting_session_id');
        }

        public function consultant(): BelongsTo
        {
            return $this->belongsTo(Consultant::class, 'consultant_id');
        }

        public function client(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'client_id');
        }

        /**
         * Scopes.
         */
        public function scopeHighRated(Builder $query): Builder
        {
            return $query->where('rating', '>=', 90);
        }

        public function scopeVerified(Builder $query): Builder
        {
            return $query->where('is_verified', true);
        }

        /**
         * Domain Methods.
         */
        public function isVerified(): bool
        {
            return $this->is_verified;
        }

        public function getFormattedRating(): string
        {
            return number_format($this->rating / 10, 1) . ' / 10.0';
        }

        public function getReviewSummary(): string
        {
            return "Review Rating: " . $this->getFormattedRating() . " | " . Str::limit($this->comment, 50);
        }

        public function setVerifiedStatus(bool $status): void
        {
            $this->update(['is_verified' => $status]);
        }

        public function respondToReview(string $responseMessage): void
        {
            $this->update([
               'response' => $responseMessage,
               'responded_at' => now(),
               'correlation_id' => (string) Str::uuid(),
            ]);
        }

        public function updateRating(int $newRating): void
        {
            if ($newRating < 1 || $newRating > 100) return;
            $this->update(['rating' => $newRating]);
        }

        public function isNegative(): bool
        {
            return $this->rating < 50;
        }
}
