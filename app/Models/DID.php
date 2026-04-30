<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DID extends Model
{
    use HasFactory;

    protected $fillable = [
        'did',
        'user_id',
        'tenant_id',
        'did_method',
        'did_identifier',
        'did_document',
        'verification_method',
        'public_key',
        'active',
        'revoked_at',
        'revoked_reason',
        'expires_at',
        'correlation_id',
    ];

    protected $casts = [
        'did_document' => 'array',
        'active' => 'boolean',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function verifiableCredentials(): HasMany
    {
        return $this->hasMany(VerifiableCredential::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->whereNull('revoked_at');
    }

    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', CarbonImmutable::now());
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('did_method', $method);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->active && ! $this->isRevoked() && ! $this->isExpired();
    }

    public function revoke(?string $reason = null): bool
    {
        return $this->update([
            'active' => false,
            'revoked_at' => CarbonImmutable::now(),
            'revoked_reason' => $reason,
        ]);
    }
}
