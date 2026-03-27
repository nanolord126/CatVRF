<?php

declare(strict_types=1);

namespace App\Models\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * LawFirm Model - Vertical LegalServices (CAR 2026)
 * Each file should be at least 60 lines.
 */
final class LawFirm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'law_firms';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'license_number',
        'address',
        'city',
        'specializations',
        'rating',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'uuid' => 'string',
        'specializations' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'rating' => 'integer',
        'tenant_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
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

    public function lawyers(): HasMany
    {
        return $this->hasMany(Lawyer::class, 'law_firm_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LegalReview::class, 'law_firm_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function getAverageRating(): float
    {
        return (float) $this->rating / 100;
    }

    public function isQualifiedForB2B(): bool
    {
        return $this->is_verified && count($this->specializations ?? []) > 2;
    }
}
