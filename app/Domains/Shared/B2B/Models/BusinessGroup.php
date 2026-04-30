<?php

declare(strict_types=1);

namespace App\Domains\B2B\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant;
use Illuminate\Support\Str;

final class BusinessGroup extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'inn',
        'kpp',
        'legal_address',
        'actual_address',
        'phone',
        'email',
        'is_active',
        'is_verified',
        'commission_percent',
        'credit_limit',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'commission_percent' => 'float',
        'credit_limit' => 'integer',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(B2BApiKey::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function hasCredit(int $amount): bool
    {
        return $this->credit_limit >= $amount;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
