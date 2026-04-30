<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ConsentRecord extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'consent_type',
        'granted',
        'granted_at',
        'revoked_at',
        'revocation_reason',
        'version',
        'expires_at',
        'ip_address',
        'user_agent',
        'correlation_id',
    ];

    protected $casts = [
        'granted' => 'bool',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeGranted($query)
    {
        return $query->where('granted', true);
    }

    public function scopeRevoked($query)
    {
        return $query->where('granted', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('consent_type', $type);
    }

    public function scopeValid($query)
    {
        return $query->where('granted', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', CarbonImmutable::now());
            });
    }

    public function isValid(): bool
    {
        return $this->granted &&
            (is_null($this->expires_at) || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return ! $this->granted && $this->revocation_reason === 'expired';
    }
}
