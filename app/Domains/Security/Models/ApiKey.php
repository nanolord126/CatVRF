<?php declare(strict_types=1);

namespace App\Domains\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

final class ApiKey extends Model
{
    protected $fillable = [
        'tenant_id',
        'key_id',
        'name',
        'key_hash',
        'key_preview',
        'permissions',
        'ip_whitelist',
        'status',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_whitelist' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' 
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
