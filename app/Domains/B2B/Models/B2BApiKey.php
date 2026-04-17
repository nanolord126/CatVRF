<?php declare(strict_types=1);

namespace App\Domains\B2B\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class B2BApiKey extends Model
{
    protected $fillable = [
        'business_group_id',
        'tenant_id',
        'name',
        'hashed_key',
        'permissions',
        'expires_at',
        'last_used_at',
        'last_ip',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'permissions' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['hashed_key'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true;
        }
        return in_array($permission, $this->permissions, true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
