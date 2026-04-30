<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VpnRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * User Login Model
 *
 * Immutable audit trail of user login attempts with VPN detection.
 * Records all authentication attempts for security monitoring and compliance.
 */
final class UserLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'login_id',
        'user_id',
        'tenant_id',
        'device_id',
        'is_vpn',
        'vpn_provider',
        'vpn_risk_level',
        'ip_address',
        'country_code',
        'country_name',
        'city',
        'asn',
        'isp',
        'user_agent',
        'device_type',
        'platform',
        'browser',
        'is_tor',
        'is_datacenter',
        'is_residential_proxy',
        'is_corporate_vpn',
        'has_behavioral_anomaly',
        'detection_sources',
        'metadata',
        'auth_method',
        'was_successful',
        'failure_reason',
        'logged_in_at',
        'logged_out_at',
        'correlation_id',
    ];

    protected $casts = [
        'is_vpn' => 'boolean',
        'is_tor' => 'boolean',
        'is_datacenter' => 'boolean',
        'is_residential_proxy' => 'boolean',
        'is_corporate_vpn' => 'boolean',
        'has_behavioral_anomaly' => 'boolean',
        'was_successful' => 'boolean',
        'detection_sources' => 'array',
        'metadata' => 'array',
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    /**
     * Relationship to the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship to the device
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    /**
     * Scope for successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('was_successful', true);
    }

    /**
     * Scope for failed logins
     */
    public function scopeFailed($query)
    {
        return $query->where('was_successful', false);
    }

    /**
     * Scope for VPN logins
     */
    public function scopeVpn($query)
    {
        return $query->where('is_vpn', true);
    }

    /**
     * Scope for Tor logins
     */
    public function scopeTor($query)
    {
        return $query->where('is_tor', true);
    }

    /**
     * Scope for specific risk level
     */
    public function scopeByRiskLevel($query, VpnRiskLevel $riskLevel)
    {
        return $query->where('vpn_risk_level', $riskLevel->value);
    }

    /**
     * Scope for high/critical risk logins
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('vpn_risk_level', [
            VpnRiskLevel::HIGH->value,
            VpnRiskLevel::CRITICAL->value,
        ]);
    }

    /**
     * Scope for recent logins (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('logged_in_at', '>=', now()->subHours(24));
    }

    /**
     * Scope for user logins
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for tenant logins
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for IP address
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope for auth method
     */
    public function scopeByAuthMethod($query, string $method)
    {
        return $query->where('auth_method', $method);
    }

    /**
     * Check if login was via VPN
     */
    public function isVpnLogin(): bool
    {
        return $this->is_vpn;
    }

    /**
     * Check if login was via Tor
     */
    public function isTorLogin(): bool
    {
        return $this->is_tor;
    }

    /**
     * Check if login was via corporate VPN (whitelisted)
     */
    public function isCorporateVpn(): bool
    {
        return $this->is_corporate_vpn;
    }

    /**
     * Get VPN risk level enum
     */
    public function getVpnRiskLevelEnum(): ?VpnRiskLevel
    {
        if ($this->vpn_risk_level === null) {
            return null;
        }

        return VpnRiskLevel::from($this->vpn_risk_level);
    }

    /**
     * Check if login is high risk
     */
    public function isHighRisk(): bool
    {
        $riskLevel = $this->getVpnRiskLevelEnum();
        if ($riskLevel === null) {
            return false;
        }

        return in_array($riskLevel, [VpnRiskLevel::HIGH, VpnRiskLevel::CRITICAL], true);
    }

    /**
     * Get session duration in seconds
     */
    public function getSessionDurationSeconds(): ?int
    {
        if ($this->logged_out_at === null) {
            return null;
        }

        return $this->logged_out_at->diffInSeconds($this->logged_in_at);
    }

    /**
     * Get session duration in human-readable format
     */
    public function getSessionDurationForHumans(): ?string
    {
        $seconds = $this->getSessionDurationSeconds();
        if ($seconds === null) {
            return null;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours} ч. {$minutes} мин.";
        }

        return "{$minutes} мин.";
    }

    /**
     * Mark login as logged out
     */
    public function markAsLoggedOut(): bool
    {
        return $this->update([
            'logged_out_at' => now(),
        ]);
    }

    /**
     * Get location summary
     */
    public function getLocationSummary(): string
    {
        $parts = [];

        if ($this->city !== null) {
            $parts[] = $this->city;
        }

        if ($this->country_name !== null) {
            $parts[] = $this->country_name;
        }

        if (empty($parts)) {
            return 'Неизвестно';
        }

        return implode(', ', $parts);
    }

    /**
     * Get VPN detection summary
     */
    public function getVpnSummary(): string
    {
        if (!$this->is_vpn) {
            return 'Без VPN';
        }

        $parts = [];

        if ($this->vpn_provider !== null) {
            $parts[] = $this->vpn_provider;
        }

        if ($this->vpn_risk_level !== null) {
            $parts[] = 'Риск: ' . $this->getVpnRiskLevelEnum()?->getLabel();
        }

        if ($this->is_tor) {
            $parts[] = 'Tor';
        }

        if ($this->is_datacenter) {
            $parts[] = 'Датацентр';
        }

        if ($this->is_residential_proxy) {
            $parts[] = 'Резидентный прокси';
        }

        return implode(', ', $parts);
    }
}
