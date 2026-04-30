<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VerifiableCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'vc_id',
        'did_id',
        'user_id',
        'tenant_id',
        'vc_type',
        'issuer_did',
        'issuer_name',
        'issuance_date',
        'expiration_date',
        'credential_subject',
        'credential_schema',
        'status',
        'revoked_at',
        'revoked_reason',
        'revoked_by',
        'proof',
        'correlation_id',
    ];

    protected $casts = [
        'credential_subject' => 'array',
        'credential_schema' => 'array',
        'proof' => 'array',
        'issuance_date' => 'datetime',
        'expiration_date' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function did(): BelongsTo
    {
        return $this->belongsTo(DID::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', CarbonImmutable::now());
            });
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiration_date', '<', CarbonImmutable::now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('vc_type', $type);
    }

    public function scopeByIssuer($query, string $issuerDid)
    {
        return $query->where('issuer_did', $issuerDid);
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
        return $this->status === 'revoked';
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function revoke(?string $reason = null, ?string $revokedBy = null): bool
    {
        return $this->update([
            'status' => 'revoked',
            'revoked_at' => CarbonImmutable::now(),
            'revoked_reason' => $reason,
            'revoked_by' => $revokedBy,
        ]);
    }
}
