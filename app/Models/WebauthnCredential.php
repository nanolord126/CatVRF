<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * WebAuthn Credential Model
 *
 * Stores passkey credentials for passwordless authentication.
 * Private key NEVER leaves the user's device (Secure Enclave/TPM).
 * Only public key is stored on server.
 */
final class WebauthnCredential extends Model
{
    use HasFactory;

    protected $table = 'webauthn_credentials';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'credential_id',
        'public_key',
        'user_handle',
        'aaguid',
        'transports',
        'counter',
        'backed_up',
        'device_type',
        'name',
        'user_agent',
        'ip_address',
        'last_used_at',
        'backup_codes',
        'recovery_enabled',
        'last_backup_code_used_at',
        'backup_codes_remaining',
        'is_compromised',
        'compromised_at',
    ];

    protected $casts = [
        'transports' => 'array',
        'counter' => 'integer',
        'backed_up' => 'boolean',
        'last_used_at' => 'datetime',
        'backup_codes' => 'array',
        'recovery_enabled' => 'boolean',
        'last_backup_code_used_at' => 'datetime',
        'backup_codes_remaining' => 'integer',
        'is_compromised' => 'boolean',
        'compromised_at' => 'datetime',
    ];

    /**
     * Relationship to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for tenant-aware queries
     */
    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    /**
     * Scope for active credentials (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if credential is backed up (synced to cloud)
     */
    public function isSynced(): bool
    {
        return $this->backed_up === true;
    }

    /**
     * Check if credential is platform authenticator (Face ID, Touch ID, Windows Hello)
     */
    public function isPlatformAuthenticator(): bool
    {
        return in_array('internal', $this->transports ?? [], true);
    }

    /**
     * Get device type label
     */
    public function getDeviceTypeLabel(): string
    {
        return match ($this->device_type) {
            'single_device' => 'Single Device',
            'syncable' => 'Syncable (Cloud)',
            default => 'Unknown',
        };
    }

    /**
     * Update counter for replay attack prevention
     */
    public function updateCounter(int $newCounter): bool
    {
        if ($newCounter <= $this->counter) {
            return false; // Replay attack detected
        }

        $this->counter = $newCounter;
        $this->last_used_at = CarbonImmutable::now();

        return $this->save();
    }

    /**
     * Mark credential as used
     */
    public function markAsUsed(?string $userAgent = null, ?string $ipAddress = null): bool
    {
        $this->last_used_at = CarbonImmutable::now();

        if ($userAgent) {
            $this->user_agent = $userAgent;
        }

        if ($ipAddress) {
            $this->ip_address = $ipAddress;
        }

        return $this->save();
    }
}
