<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProxyType;
use App\Enums\ProxyProvider;
use App\Enums\ResidentialProxyRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Residential Proxy Detection Model
 *
 * Represents a residential proxy detection event.
 * Logged for audit and analysis purposes.
 */
final class ResidentialProxyDetection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'ip_address',
        'country_code',
        'city',
        'proxy_type',
        'proxy_provider',
        'risk_level',
        'confidence_score',
        'is_residential_proxy',
        'is_rotating',
        'is_ethical',
        'is_isp_backed',
        'has_behavioral_anomaly',
        'geo_mismatch',
        'is_russian_territory_violation',
        'detection_sources',
        'metadata',
        'correlation_id',
        'detected_at',
    ];

    protected $casts = [
        'is_residential_proxy' => 'boolean',
        'is_rotating' => 'boolean',
        'is_ethical' => 'boolean',
        'is_isp_backed' => 'boolean',
        'has_behavioral_anomaly' => 'boolean',
        'geo_mismatch' => 'boolean',
        'is_russian_territory_violation' => 'boolean',
        'detection_sources' => 'array',
        'metadata' => 'array',
        'detected_at' => 'datetime',
        'confidence_score' => 'integer',
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
     * Scope for residential proxy detections
     */
    public function scopeResidentialProxy($query)
    {
        return $query->where('is_residential_proxy', true);
    }

    /**
     * Scope for high risk detections
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [
            ResidentialProxyRiskLevel::HIGH->value,
            ResidentialProxyRiskLevel::CRITICAL->value,
            ResidentialProxyRiskLevel::PERMANENT_BLOCK->value,
        ]);
    }

    /**
     * Scope for Russian territory violations
     */
    public function scopeRussianTerritoryViolation($query)
    {
        return $query->where('is_russian_territory_violation', true);
    }

    /**
     * Scope for specific provider
     */
    public function scopeByProvider($query, ProxyProvider $provider)
    {
        return $query->where('proxy_provider', $provider->value);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('detected_at', [$startDate, $endDate]);
    }

    /**
     * Get proxy type enum instance
     */
    public function getProxyTypeEnum(): ProxyType
    {
        return ProxyType::from($this->proxy_type);
    }

    /**
     * Get proxy provider enum instance
     */
    public function getProxyProviderEnum(): ProxyProvider
    {
        return ProxyProvider::from($this->proxy_provider);
    }

    /**
     * Get risk level enum instance
     */
    public function getRiskLevelEnum(): ResidentialProxyRiskLevel
    {
        return ResidentialProxyRiskLevel::from($this->risk_level);
    }
}
