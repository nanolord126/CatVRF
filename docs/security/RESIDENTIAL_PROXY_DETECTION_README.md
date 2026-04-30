# Residential Proxy Detection System

## Overview

The Residential Proxy Detection System is a multi-layered security feature for CatVRF that detects and mitigates threats from residential proxies. Residential proxies are particularly dangerous because they use real home/mobile IP addresses, making them difficult to distinguish from legitimate users.

## Why Residential Proxies Are a Threat

Residential proxies are used for:
- Mass account registration (bypassing ContactIsolation)
- Client base hunting (email/phone lookup)
- Automated KYB and verification
- Insider scraping (employees masking their identity)
- Fraud and credential stuffing

In 2026, residential proxies have become more sophisticated with:
- Ethical sourcing claims
- AI-powered rotation
- Device fingerprint spoofing
- ISP-backed networks

## Architecture

### Multi-Layered Detection

**Layer 1: IP Intelligence & Proxy Databases**
- IPinfo Residential Proxy Detection API
- MaxMind Proxy Detection + GeoIP2
- FraudScore, GetIPIntel, AbuseIPDB
- Custom database of known residential ranges (updated via FstecBduService)

**Layer 2: Behavioral & Device Fingerprint Analysis**
- Integration with BehavioralBiometricsService
- Detection of ideal/perfect patterns (too consistent)
- Device fingerprint mismatch with region
- High velocity requests from "home" IPs

**Layer 3: Geo + Session Consistency**
- IP/geo mismatch detection
- Special rules for Russian territories (Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia)
- Residential proxy from foreign country to Russian territory = CRITICAL risk

**Layer 4: Honeypot + Challenge**
- Turnstile / invisible CAPTCHA for medium/high risk
- Honeypot fields in forms

### Services

- **ResidentialProxyDetectionService**: Core detection logic
- **GeoTerritoryService**: Russian territory validation
- **VpnDetectionService**: Integrated with residential proxy detection
- **BotDetectionService**: Combined bot + residential proxy signals

## Risk Levels and Protection Measures

### Low Risk
- **Detection**: Logging, DeviceManagement tag
- **Protection**: Light rate limiting
- **Cooldown**: 0 hours

### Medium Risk
- **Detection**: Managed Challenge (Turnstile)
- **Protection**: Cooldown 12-24 hours
- **Cooldown**: 12 hours
- **Notifications**: Tenant owners notified

### High Risk
- **Detection**: Block 24-72 hours
- **Protection**: Invalidate SplitKey
- **Cooldown**: 24 hours
- **Notifications**: Tenant owners, manual review required

### Critical Risk
- **Detection**: Block 72+ hours
- **Protection**: Invalidate SplitKey, logout all sessions
- **Cooldown**: 72 hours
- **Notifications**: Tenant owners, super-admin, manual review

### Permanent Block
- **Detection**: Permanent IP/User block
- **Protection**: Blacklist, invalidate SplitKey, logout all sessions
- **Cooldown**: N/A (permanent)
- **Notifications**: Super-admin, manual review

## Zero Tolerance Policy

CatVRF enforces a **Zero Tolerance for Residential Proxy Abuse in High-Risk Actions**.

### Zero Tolerance Triggers

1. **Residential proxy + mass registration** - Automatic permanent block
2. **Residential proxy + hunting pattern** - Automatic permanent block
3. **Residential proxy + insider scraping** - Automatic permanent block
4. **Residential proxy + RF territory violation** - Automatic permanent block
5. **Residential proxy + behavioral critical** - Automatic permanent block

### Sensitive Routes

All routes that require strict proxy detection:
- `api/auth/register`
- `api/auth/login`
- `api/auth/kyc/*`
- `api/kyb/*`
- `api/wallet/withdraw`
- `api/wallet/transfer`
- `api/users/*/bank-accounts`
- `api/tenants/*/staff`

## Configuration

Edit `config/proxy-detection.php` to configure:

### Providers

```php
'providers' => [
    'ipinfo' => [
        'enabled' => env('IPINFO_ENABLED', false),
        'api_key' => env('IPINFO_API_KEY'),
    ],
    'maxmind' => [
        'enabled' => env('MAXMIND_ENABLED', false),
        'database_path' => env('MAXMIND_DATABASE_PATH'),
    ],
    // ... other providers
],
```

### Thresholds

```php
'thresholds' => [
    'low' => [
        'confidence_score' => 0,
        'behavioral_score' => 0.85,
    ],
    'medium' => [
        'confidence_score' => 30,
        'behavioral_score' => 0.75,
    ],
    'high' => [
        'confidence_score' => 60,
        'behavioral_score' => 0.60,
    ],
    'critical' => [
        'confidence_score' => 85,
        'behavioral_score' => 0.40,
    ],
],
```

### Russian Territories

```php
'russian_territories' => [
    'enabled' => true,
    'territories' => [
        'Crimea', 'Crimean Federal District',
        'Sevastopol',
        'Donetsk People\'s Republic', 'DPR',
        'Luhansk People\'s Republic', 'LPR',
        'Kherson Oblast', 'Kherson Region',
        'Zaporizhzhia Oblast', 'Zaporizhzhia Region',
    ],
    'rules' => [
        'residential_proxy_from_foreign_country' => 'CRITICAL',
        'residential_proxy_to_foreign_country' => 'HIGH',
        'geo_mismatch' => 'CRITICAL',
    ],
],
```

## Integration

### In Services

```php
use App\Services\Security\ResidentialProxyDetectionService;

class YourService
{
    public function __construct(
        private readonly ResidentialProxyDetectionService $residentialProxyDetection,
    ) {}

    public function performAction(Request $request, User $user): void
    {
        $result = $this->residentialProxyDetection->detect($request, $user);
        
        if ($result->requiresProtection()) {
            $this->residentialProxyDetection->applyProtection($user, $result);
            throw new SecurityException('Residential proxy detected');
        }
        
        // Continue with action
    }
}
```

### In Middleware

```php
public function handle(Request $request, Closure $next)
{
    $result = app(ResidentialProxyDetectionService::class)->detect($request, auth()->user());
    
    if ($result->requiresProtection()) {
        return response()->json(['error' => 'Residential proxy detected'], 403);
    }
    
    return $next($request);
}
```

## Database Tables

### residential_proxy_detections

Logs all residential proxy detection events for audit and analysis.

### residential_proxy_blacklist

Blacklisted IPs with permanent or temporary blocks.

### residential_proxy_whitelist

Whitelisted IPs (corporate VPNs, etc.)

## Monitoring

### ClickHouse Queries

```sql
-- Residential proxy detections by risk level
SELECT 
    risk_level,
    COUNT(*) as count,
    COUNT(DISTINCT user_id) as affected_users
FROM residential_proxy_detections
WHERE detected_at >= now() - INTERVAL 1 DAY
GROUP BY risk_level;

-- Top proxy providers
SELECT 
    proxy_provider,
    COUNT(*) as count,
    AVG(confidence_score) as avg_confidence
FROM residential_proxy_detections
WHERE detected_at >= now() - INTERVAL 1 DAY
GROUP BY proxy_provider
ORDER BY count DESC
LIMIT 10;

-- Russian territory violations
SELECT 
    COUNT(*) as violations,
    COUNT(DISTINCT user_id) as affected_users
FROM residential_proxy_detections
WHERE is_russian_territory_violation = true
AND detected_at >= now() - INTERVAL 1 DAY;
```

## Scheduled Jobs

```php
// config/proxy-detection.php
'jobs' => [
    'update_ip_intelligence' => [
        'enabled' => true,
        'schedule' => '0 */6 * * *', // Every 6 hours
    ],
    'update_tor_exit_nodes' => [
        'enabled' => true,
        'schedule' => '0 */12 * * *', // Every 12 hours
    ],
    'cleanup_old_detections' => [
        'enabled' => true,
        'schedule' => '0 0 * * 0', // Weekly
        'retention_days' => 90,
    ],
],
```

## Testing

Run Pest tests:

```bash
php artisan test tests/Unit/Security/ResidentialProxyDetectionServiceTest.php
php artisan test tests/Unit/Security/GeoTerritoryServiceTest.php
```

## Production Guide

See [PRODUCTION_GUIDE.md](PRODUCTION_GUIDE.md) for:
- Cloudflare Bot Management integration
- IP intelligence database updates
- Monitoring and alerting setup
- Performance optimization

## Compliance

This system is designed to comply with:
- 152-ФЗ (Personal Data Protection)
- ФЗ-323 (Healthcare)
- FSTEC BDU requirements
- 152-ФЗ territorial restrictions for Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia

## Support

For issues or questions, contact the security team at security@catvrf.ru
