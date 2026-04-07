<?php declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Consultant extends Model
{
    use HasFactory, SoftDeletes;

        protected $table = 'consultants';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'consulting_firm_id',
            'full_name',
            'title',
            'bio',
            'specializations',
            'experience_years',
            'hourly_rate',
            'rating',
            'is_active',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'consulting_firm_id' => 'integer',
            'specializations' => 'json',
            'tags' => 'json',
            'hourly_rate' => 'integer',
            'rating' => 'integer',
            'is_active' => 'boolean',
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
        public function firm(): BelongsTo
        {
            return $this->belongsTo(ConsultingFirm::class, 'consulting_firm_id');
        }

        public function services(): HasMany
        {
            return $this->hasMany(ConsultingService::class, 'consultant_id');
        }

        public function sessions(): HasMany
        {
            return $this->hasMany(ConsultingSession::class, 'consultant_id');
        }

        public function projects(): HasMany
        {
            return $this->hasMany(ConsultingProject::class, 'consultant_id');
        }

        /**
         * Scopes.
         */
        public function scopeActive(Builder $query): Builder
        {
            return $query->where('is_active', true);
        }

        public function scopeExperienced(Builder $query, int $years = 10): Builder
        {
            return $query->where('experience_years', '>=', $years);
        }

        /**
         * Domain Methods.
         */
        public function getFormattedHourlyRate(): string
        {
            return number_format($this->hourly_rate / 100, 2) . ' RUB/hr';
        }

        public function getFullTitle(): string
        {
            return $this->full_name . ' (' . $this->title . ')';
        }

        public function isExpertIn(string $skill): bool
        {
            return in_array($skill, $this->specializations ?? []);
        }

        public function hasLowRateForExpert(int $maxRate = 1000000): bool
        {
            return $this->hourly_rate <= $maxRate && $this->experience_years >= 15;
        }

        public function getSuccessRate(): float
        {
            $totalSessions = $this->sessions()->count();
            if ($totalSessions === 0) return 0.0;

            $completedSessions = $this->sessions()->where('status', 'completed')->count();
            return round(($completedSessions / $totalSessions) * 100, 2);
        }
}
