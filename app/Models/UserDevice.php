<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BotRiskLevel;
use App\Enums\VpnRiskLevel;
use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserDevice extends Model
{
    use HasFactory;

    public const DEVICE_TYPE_MOBILE = 'mobile';

    public const DEVICE_TYPE_TABLET = 'tablet';

    public const DEVICE_TYPE_DESKTOP = 'desktop';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'device_name',
        'device_type',
        'fingerprint',
        'platform',
        'browser',
        'user_agent',
        'ip_address',
        'is_vpn',
        'vpn_provider',
        'vpn_risk_level',
        'vpn_detection_data',
        'vpn_protection_risk_level',
        'vpn_protection_risk_factors',
        'vpn_protection_block_applied',
        'vpn_protection_blocked_at',
        'vpn_protection_block_expires_at',
        'vpn_protection_correlation_id',
        'last_used_at',
        'is_revoked',
        'is_trusted',
        'is_current',
        'first_seen_at',
        'last_seen_at',
        'last_authenticated_at',
        'auth_count',
        'location_country',
        'location_city',
        'meta',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_authenticated_at' => 'datetime',
        'last_bot_detection_at' => 'datetime',
        'is_revoked' => 'boolean',
        'is_trusted' => 'boolean',
        'is_current' => 'boolean',
        'vpn_protection_risk_factors' => 'json',
        'vpn_protection_block_applied' => 'boolean',
        'vpn_protection_blocked_at' => 'datetime',
        'vpn_protection_block_expires_at' => 'datetime',
        'is_vpn' => 'boolean',
        'auth_count' => 'integer',
        'bot_detection_count' => 'integer',
        'meta' => 'json',
        'vpn_detection_data' => 'json',
        'bot_detection_data' => 'json',
    ];

    protected $table = 'user_devices';

    // ========================
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // RELATIONSHIPS
    // ========================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeActive($query)
    {
        return $query->where('is_revoked', false);
    }

    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    public function scopeByFingerprint($query, string $fingerprint)
    {
        return $query->where('fingerprint', $fingerprint);
    }

    public function scopeVpn($query)
    {
        return $query->where('is_vpn', true);
    }

    public function scopeByRiskLevel($query, VpnRiskLevel $riskLevel)
    {
        return $query->where('vpn_risk_level', $riskLevel->value);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('vpn_risk_level', [
            VpnRiskLevel::HIGH->value,
            VpnRiskLevel::CRITICAL->value,
        ]);
    }
public function scopeBotDetected($query)
    {
        return $query->whereNotNull('bot_risk_level')
            ->where('bot_risk_level', '!=', 'low');
    }

    public function scopeHighBotRisk($query)
    {
        return $query->whereIn('bot_risk_level', [
            BotRiskLevel::HIGH->value,
            BotRiskLevel::CRITICAL->value,
        ]);
    }

    public function scopeVpnProtectionBlocked($query)
    {
        return $query->where('vpn_protection_block_applied', true)
            ->where(function ($query) {
                $query->whereNull('vpn_protection_block_expires_at')
                    ->orWhere('vpn_protection_block_expires_at', '>', CarbonImmutable::now());
            });
    }

    public function scopeVpnProtectionExpired($query)
    {
        return $query->where('vpn_protection_block_applied', true)
            ->where('vpn_protection_block_expires_at', '<=', CarbonImmutable::now());
    }

    
    // ========================
    // METHODS
    // ========================

    /**
     * Revoke device
     */
    public function revoke(): bool
    {
        return $this->update(['is_revoked' => true]);
    }

    /**
     * Mark device as trusted
     */
    public function markAsTrusted(): bool
    {
        return $this->update(['is_trusted' => true]);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(?string $ipAddress = null, ?string $userAgent = null): bool
    {
        return $this->update([
            'last_used_at' => CarbonImmutable::now(),
            'ip_address' => $ipAddress ?? $this->ip_address,
            'user_agent' => $userAgent ?? $this->user_agent,
        ]);
    }

    /**
     * Check if device is from suspicious location
     */
    public function isSuspiciousLocation(): bool
    {
        if (! $this->location_country || ! $this->location_city) {
            return false;
        }

        $recentDevices = $this->user()
            ->devices()
            ->active()
            ->where('id', '!=', $this->id)
            ->where('last_used_at', '>=', CarbonImmutable::now()->subDays(7))
            ->get();

        foreach ($recentDevices as $device) {
            if ($device->location_country !== $this->location_country) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark device as current (only one current device per user)
     */
    public function markAsCurrent(): void
    {
        UserDevice::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->update(['is_current' => true]);
    }

    /**
     * Record authentication event
     */
    public function recordAuthentication(): void
    {
        $this->update([
            'last_authenticated_at' => CarbonImmutable::now(),
            'last_seen_at' => CarbonImmutable::now(),
            'last_used_at' => CarbonImmutable::now(),
            'auth_count' => ($this->auth_count ?? 0) + 1,
        ]);
    }

    /**
     * Check if this is a new device
     */
    public function isNewDevice(): bool
    {
        return ($this->auth_count ?? 0) === 1;
    }

    /**
     * Check if device was recently seen
     */
    public function isRecentlySeen(int $hours = 24): bool
    {
        $seenAt = $this->last_seen_at ?? $this->last_used_at;

        return $seenAt && $seenAt->gt(CarbonImmutable::now()->subHours($hours));
    }


    /**
     * Update VPN detection information
     */
    public function updateVpnInfo(
        bool $isVpn,
        ?string $provider = null,
        ?VpnRiskLevel $riskLevel = null,
        ?array $detectionData = null
    ): bool {
        return $this->update([
            'is_vpn' => $isVpn,
            'vpn_provider' => $provider,
            'vpn_risk_level' => $riskLevel?->value,
            'vpn_detection_data' => $detectionData,
        ]);
    }

    /**
     * Check if device is VPN
     */
    public function isVpn(): bool
    {
        return $this->is_vpn ?? false;
    }

    /**
     * Check if device is high risk VPN
     */
    public function isHighRiskVpn(): bool
    {
        if (!$this->is_vpn || $this->vpn_risk_level === null) {
            return false;
        }

        $riskLevel = VpnRiskLevel::tryFrom($this->vpn_risk_level);
        return $riskLevel !== null && in_array($riskLevel, [VpnRiskLevel::HIGH, VpnRiskLevel::CRITICAL], true);
    }

    /**
     * Get VPN risk level enum
     */
    public function getVpnRiskLevelEnum(): ?VpnRiskLevel
    {
        return $this->vpn_risk_level !== null ? VpnRiskLevel::tryFrom($this->vpn_risk_level) : null;
    }

    /**
     * Get VPN summary for display
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

        $riskLevel = $this->getVpnRiskLevelEnum();
        if ($riskLevel !== null) {
            $parts[] = 'Риск: ' . $riskLevel->getLabel();
        }

        return implode(', ', $parts);
    }

    /**
     * Check if device has frequent VPN logins
     */
    public function hasFrequentVpnLogins(int $threshold = 3): bool
    {
        if (!$this->is_vpn) {
            return false;
        }

        return ($this->auth_count ?? 0) >= $threshold;
    }
    /**
     * Get days since first seen
     */
    public function daysSinceFirstSeen(): int

    // ========================
    // BOT DETECTION METHODS
    // ========================

    /**
     * Update bot detection information
     */
    public function updateBotDetection(
        ?BotRiskLevel $riskLevel = null,
        ?array $detectionData = null
    ): bool {
        $updateData = [
            'last_bot_detection_at' => CarbonImmutable::now(),
            'bot_detection_count' => ($this->bot_detection_count ?? 0) + 1,
        ];

        if ($riskLevel !== null) {
            $updateData['bot_risk_level'] = $riskLevel->value;
        }

        if ($detectionData !== null) {
            $updateData['bot_detection_data'] = $detectionData;
        }

        return $this->update($updateData);
    }

    /**
     * Check if device has been detected as bot
     */
    public function isBotDetected(): bool
    {
        return $this->bot_risk_level !== null && $this->bot_risk_level !== 'low';
    }

    /**
     * Check if device has high bot risk
     */
    public function isHighBotRisk(): bool
    {
        if ($this->bot_risk_level === null) {
            return false;
        }

        $riskLevel = BotRiskLevel::tryFrom($this->bot_risk_level);
        return $riskLevel !== null && in_array($riskLevel, [BotRiskLevel::HIGH, BotRiskLevel::CRITICAL], true);
    }

    /**
     * Get bot risk level enum
     */
    public function getBotRiskLevelEnum(): ?BotRiskLevel
    {
        return $this->bot_risk_level !== null ? BotRiskLevel::tryFrom($this->bot_risk_level) : null;
    }

    /**
     * Get bot risk summary for display
     */
    public function getBotRiskSummary(): string
    {
        if (!$this->isBotDetected()) {
            return 'Безопасно';
        }

        $riskLevel = $this->getBotRiskLevelEnum();
        if ($riskLevel === null) {
            return 'Неизвестно';
        }

        $parts = [$riskLevel->getLabel()];
        $parts[] = "Обнаружений: {$this->bot_detection_count}";

        return implode(', ', $parts);
    }

    /**
     * Check if device should be auto-disabled due to high bot risk
     */
    public function shouldAutoDisable(): bool
    {
        if (!$this->isHighBotRisk()) {
            return false;
        }

        // Auto-disable if high bot risk and multiple detections
        return ($this->bot_detection_count ?? 0) >= 3;
    }

    /**
     * Check if device has recent bot detection
     */
    public function hasRecentBotDetection(int $hours = 24): bool
    {
        $detectedAt = $this->last_bot_detection_at;

        return $detectedAt && $detectedAt->gt(CarbonImmutable::now()->subHours($hours));
    }

    /**
     * Get combined risk summary (VPN + Bot)
     */
    public function getCombinedRiskSummary(): string
    {
        $risks = [];

        if ($this->isHighRiskVpn()) {
            $risks[] = 'VPN: Высокий риск';
        }

        if ($this->isHighBotRisk()) {
            $risks[] = 'Бот: Высокий риск';
        }

        if (empty($risks)) {
            return 'Низкий риск';
        }

        return implode('; ', $risks);
    }
    {
        $firstSeen = $this->first_seen_at ?? $this->created_at;

        return $firstSeen ? $firstSeen->diffInDays(CarbonImmutable::now()) : 0;
    }

    /**
     * Get days since last authentication
     */
    public function daysSinceLastAuth(): int
    {
        $lastAuth = $this->last_authenticated_at ?? $this->last_used_at;

        return $lastAuth ? $lastAuth->diffInDays(CarbonImmutable::now()) : PHP_INT_MAX;
    }
}
            'vpn_protection_risk_factors' => $riskFactors,
            'vpn_protection_block_applied' => $blockApplied,
        ];

        if ($blockApplied) {
            $updateData['vpn_protection_blocked_at'] = CarbonImmutable::now();
            $protectionConfig = config("vpn-protection.protection_levels.{$riskLevel}", []);
            $cooldownHours = $protectionConfig['cooldown_hours'] ?? 24;
            $updateData['vpn_protection_block_expires_at'] = CarbonImmutable::now()->addHours($cooldownHours);
        }

        if ($correlationId !== null) {
            $updateData['vpn_protection_correlation_id'] = $correlationId;
        }

        return $this->update($updateData);
    }

    /**
     * Check if VPN protection block is active
     */
    public function isVpnProtectionBlocked(): bool
    {
        if (!$this->vpn_protection_block_applied) {
            return false;
        }

        if ($this->vpn_protection_block_expires_at === null) {
            return true; // Permanent block
        }

        return $this->vpn_protection_block_expires_at->gt(CarbonImmutable::now());
    }

    /**
     * Get VPN protection summary for display
     */
    public function getVpnProtectionSummary(): string
    {
        if (!$this->vpn_protection_block_applied) {
            return 'Нет ограничений';
        }

        $parts = [];

        if ($this->vpn_protection_risk_level !== null) {
            $parts[] = 'Риск: ' . ucfirst($this->vpn_protection_risk_level);
        }

        if ($this->vpn_protection_blocked_at !== null) {
            $parts[] = 'Заблокирован: ' . $this->vpn_protection_blocked_at->format('d.m.Y H:i');
        }

        if ($this->vpn_protection_block_expires_at !== null) {
            if ($this->vpn_protection_block_expires_at->gt(CarbonImmutable::now())) {
                $parts[] = 'Истекает: ' . $this->vpn_protection_block_expires_at->format('d.m.Y H:i');
            } else {
                $parts[] = 'Истёк';
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Get days since first seen
     */
    public function daysSinceFirstSeen(): int
    {
        $firstSeen = $this->first_seen_at ?? $this->created_at;

        return $firstSeen ? $firstSeen->diffInDays(CarbonImmutable::now()) : 0;
    }

    /**
     * Get days since last authentication
     */
    public function daysSinceLastAuth(): int
    {
        $lastAuth = $this->last_authenticated_at ?? $this->last_used_at;

        return $lastAuth ? $lastAuth->diffInDays(CarbonImmutable::now()) : PHP_INT_MAX;
    }
}
