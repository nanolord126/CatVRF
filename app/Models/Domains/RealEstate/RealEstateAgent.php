<?php

declare(strict_types=1);

namespace App\Models\Domains\RealEstate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Models\User;

final class RealEstateAgent extends Model
{
    protected $table = 'real_estate_agents';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'full_name',
        'license_number',
        'phone',
        'email',
        'rating',
        'deals_count',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'deals_count' => 'integer',
        'is_active' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant') && app('tenant') instanceof Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });

        static::creating(function (Model $model): void {
            if (!$model->uuid) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->where('rating', '>=', 4.5);
    }

    public function scopeExperienced(Builder $query): Builder
    {
        return $query->where('deals_count', '>=', 10);
    }
}
