<?php declare(strict_types=1);

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Support\Str;

final class Lawyer extends Model
{

        protected $table = 'lawyers';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'law_firm_id',
            'user_id',
            'full_name',
            'registration_number',
            'categories',
            'experience_years',
            'consultation_price',
            'is_active',
            'correlation_id',
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'law_firm_id' => 'integer',
            'user_id' => 'integer',
            'categories' => 'json',
            'experience_years' => 'integer',
            'consultation_price' => 'integer', // in cents
            'is_active' => 'boolean',
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

        public function firm(): BelongsTo
        {
            return $this->belongsTo(LawFirm::class, 'law_firm_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        public function consultations(): HasMany
        {
            return $this->hasMany(LegalConsultation::class, 'lawyer_id');
        }

        public function reviews(): HasMany
        {
            return $this->hasMany(LegalReview::class, 'lawyer_id');
        }

        public function scopeActive(Builder $query): Builder
        {
            return $query->where('is_active', true);
        }

        public function scopeInCategory(Builder $query, string $category): Builder
        {
            return $query->whereJsonContains('categories', $category);
        }

        public function isPartnerInFirm(): bool
        {
            return !empty($this->law_firm_id);
        }

        public function getConsultationPriceInRub(): float
        {
            return (float) ($this->consultation_price / 100);
        }

        public function canHandleCriminalCase(): bool
        {
            return in_array('criminal', $this->categories ?? []) && $this->experience_years > 5;
        }
}
