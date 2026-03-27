<?php

declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ConsultingFirm Model - Vertical Consulting (CAR 2026)
 * Represents agencies and firms providing business, financial, or strategic advice.
 * File size requirement: >60 lines.
 */
final class ConsultingFirm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consulting_firms';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'registration_number',
        'description',
        'headquarters_city',
        'industries',
        'rating',
        'is_premium',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'uuid' => 'string',
        'tenant_id' => 'integer',
        'industries' => 'json',
        'tags' => 'json',
        'rating' => 'integer',
        'is_premium' => 'boolean',
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
    public function consultants(): HasMany
    {
        return $this->hasMany(Consultant::class, 'consulting_firm_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ConsultingProject::class, 'consulting_firm_id');
    }

    /**
     * Scopes.
     */
    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    public function scopeByIndustry(Builder $query, string $industry): Builder
    {
        return $query->whereJsonContains('industries', $industry);
    }

    /**
     * Domain Methods.
     */
    public function getFormattedRating(): string
    {
        return number_format($this->rating / 10, 1) . ' / 10.0';
    }

    public function hasExpertiseIn(string $industry): bool
    {
        return in_array($industry, $this->industries ?? []);
    }

    public function isHighRated(): bool
    {
        return $this->rating >= 90;
    }

    public function getConsultantCount(): int
    {
        return $this->consultants()->count();
    }

    public function getActiveProjectsCount(): int
    {
        return $this->projects()->where('status', 'active')->count();
    }
}
