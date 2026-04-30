<?php

declare(strict_types=1);

namespace App\Models;

use Hasher;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Hashing\Hasher;

final class ServiceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'tenant_id',
        'client_id',
        'client_secret_hash',
        'certificate_fingerprint',
        'certificate_pem',
        'scopes',
        'permissions',
        'status',
        'last_used_at',
        'suspended_at',
        'suspended_reason',
        'revoked_by',
        'revoked_at',
        'revoked_reason',
        'expires_at',
        'correlation_id',
    ];

    protected $casts = [
        'scopes' => 'array',
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'suspended_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret_hash',
        'certificate_pem',
    ];

    public function __construct(
        private readonly Hasher $hasher,
    ) {}

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', CarbonImmutable::now());
            });
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', CarbonImmutable::now());
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByClientId($query, string $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByCertificateFingerprint($query, string $fingerprint)
    {
        return $query->where('certificate_fingerprint', $fingerprint);
    }

    public function setClientSecretAttribute(string $value): void
    {
        $this->attributes['client_secret_hash'] = $this->hasher /* TODO: inject via constructor DI */ /* TODO: inject via DI */->make($value);
    }

    public function verifyClientSecret(string $secret): bool
    {
        return $this->hasher /* TODO: inject via constructor DI */ /* TODO: inject via DI */->check($secret, $this->client_secret_hash);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended' && $this->suspended_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked' && $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function suspend(string $reason, string $suspendedBy): bool
    {
        return $this->update([
            'status' => 'suspended',
            'suspended_at' => CarbonImmutable::now(),
            'suspended_reason' => $reason,
        ]);
    }

    public function revoke(string $reason, string $revokedBy): bool
    {
        return $this->update([
            'status' => 'revoked',
            'revoked_at' => CarbonImmutable::now(),
            'revoked_by' => $revokedBy,
            'revoked_reason' => $reason,
        ]);
    }

    public function updateLastUsed(): bool
    {
        return $this->update(['last_used_at' => CarbonImmutable::now()]);
    }
}
