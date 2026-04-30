# VPN Detection & Control System

**Production 2026 Enterprise Security** — Multi-layered VPN/Proxy/Tor detection system for CatVRF marketplace with gradient protection measures, immutable audit, and Russian territories compliance.

## Overview

The VPN Detection System provides comprehensive protection against unauthorized access through VPNs, proxies, Tor, and anonymizers. It implements:

- **Multi-layered detection**: IP intelligence, ASN analysis, Tor/I2P detection, residential proxy detection
- **Gradient protection**: Risk-based response (LOW → MEDIUM → HIGH → CRITICAL)
- **Immutable audit**: All VPN logins recorded in `user_logins` and `security_events`
- **Russian territories compliance**: Special rules for Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia
- **Integration**: Seamless integration with Cooldown, SplitKey, Behavioral Biometrics, Device Management

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Authentication Flow                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  User Login Request                                              │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────────────────────────────────┐                     │
│  │  VpnAuthenticationIntegrationService     │                     │
│  │  - processSuccessfulLogin()             │                     │
│  │  - processFailedLogin()                 │                     │
│  └─────────────────────────────────────────┘                     │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────────────────────────────────┐                     │
│  │       VpnDetectionService               │                     │
│  │  - Multi-layer detection                │                     │
│  │  - Risk calculation                     │                     │
│  │  - applyProtection()                    │                     │
│  └─────────────────────────────────────────┘                     │
│       │                                                          │
│       ├──────────────┬──────────────┬──────────────┐            │
│       ▼              ▼              ▼              ▼            │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐            │
│  │ IP Intel│  │   ASN   │  │   Tor   │  │Behavioral│            │
│  │ MaxMind │  │ Analysis│  │  Exit   │  │Biometrics│            │
│  │IP2Loc   │  │         │  │  Nodes  │  │          │            │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘            │
│                                                                  │
│  Result: VpnDetectionResult (risk level, sources, metadata)      │
│                                                                  │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────────────────────────────────┐                     │
│  │          Protection Measures             │                     │
│  ├─────────────────────────────────────────┤                     │
│  │ • CooldownService (12h/24h/72h)         │                     │
│  │ • SplitKeyService (invalidation)        │                     │
│  │ • Session termination (CRITICAL)        │                     │
│  │ • Notifications (tenant owners)         │                     │
│  └─────────────────────────────────────────┘                     │
│                                                                  │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────────────────────────────────┐                     │
│  │          Immutable Audit                 │                     │
│  ├─────────────────────────────────────────┤                     │
│  │ • user_logins (every login)             │                     │
│  │ • security_events (VPN only)            │                     │
│  │ • UserDevice (VPN status tracking)      │                     │
│  └─────────────────────────────────────────┘                     │
└─────────────────────────────────────────────────────────────────┘
```

## Risk Levels

| Level | Description | Cooldown | Split Key | Session Term | Manual Review |
|-------|-------------|----------|-----------|--------------|---------------|
| **LOW** | No VPN or corporate VPN (whitelisted) | 0h | No | No | No |
| **MEDIUM** | Commercial VPN without red flags | 12h | No | No | No |
| **HIGH** | Tor, datacenter proxy, behavioral anomaly | 24h | Yes | No | Yes |
| **CRITICAL** | Tor + anomaly, residential proxy + geo mismatch | 72h | Yes | Yes | Yes |

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This creates/updates:
- `user_logins` table with VPN fields
- `user_devices` table with VPN fields
- `security_events` table with VPN fields

### 2. Configure Providers

Edit `config/vpn-detection.php`:

```php
'providers' => [
    'maxmind' => [
        'enabled' => env('MAXMIND_ENABLED', true),
        'database_path' => env('MAXMIND_DATABASE_PATH', database_path('geoip/GeoLite2-City.mmdb')),
        'license_key' => env('MAXMIND_LICENSE_KEY'),
    ],
    'abuseipdb' => [
        'enabled' => env('ABUSEIPDB_ENABLED', true),
        'api_key' => env('ABUSEIPDB_API_KEY'),
    ],
    // ... other providers
],
```

### 3. Set Environment Variables

```env
# VPN Detection
VPN_DETECTION_ENABLED=true

# MaxMind GeoIP2
MAXMIND_ENABLED=true
MAXMIND_LICENSE_KEY=your_license_key
MAXMIND_ACCOUNT_ID=your_account_id

# AbuseIPDB
ABUSEIPDB_ENABLED=true
ABUSEIPDB_API_KEY=your_api_key

# GetIPIntel
GETIPINTEL_ENABLED=true
GETIPINTEL_API_KEY=your_contact_email
```

### 4. Corporate VPN Whitelist

Add your corporate VPN ranges to `config/vpn-detection.php`:

```php
'corporate_vpn' => [
    'whitelist' => [
        'ips' => [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ],
        'asns' => [
            12345, // Your corporate ASN
        ],
    ],
],
```

## Usage

### Integration in Authentication Flow

```php
use App\Services\Security\VpnAuthenticationIntegrationService;
use App\Models\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        private readonly VpnAuthenticationIntegrationService $vpnIntegration
    ) {}

    public function login(Request $request)
    {
        // ... authenticate user
        
        $user = User::where('email', $request->email)->first();
        
        if ($user && Hash::check($request->password, $user->password)) {
            // Process VPN detection
            $vpnResult = $this->vpnIntegration->processSuccessfulLogin(
                user: $user,
                request: $request,
                authMethod: 'password',
                device: $device // optional
            );
            
            if ($vpnResult['protection_applied']) {
                // User may be under cooldown
                return response()->json([
                    'message' => 'Login successful but security measures applied',
                    'vpn_detected' => $vpnResult['vpn_detected'],
                    'risk_level' => $vpnResult['risk_level'],
                ]);
            }
            
            return response()->json(['message' => 'Login successful']);
        }
        
        // Failed login
        $this->vpnIntegration->processFailedLogin(
            user: $user,
            request: $request,
            failureReason: 'Invalid credentials',
            authMethod: 'password'
        );
        
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
}
```

### Checking VPN Status for Sensitive Operations

```php
use App\Services\Security\VpnAuthenticationIntegrationService;

class BankAccountController extends Controller
{
    public function update(Request $request)
    {
        $check = $this->vpnIntegration->canPerformSensitiveOperation(
            user: $request->user(),
            request: $request
        );
        
        if (!$check['allowed']) {
            if ($check['reason'] === 'High-risk VPN detected') {
                return response()->json([
                    'error' => 'Cannot perform this operation via high-risk VPN',
                ], 403);
            }
            
            if ($check['reason'] === 'VPN cooldown active') {
                return response()->json([
                    'error' => 'VPN cooldown active',
                    'remaining_seconds' => $check['cooldown_remaining'],
                ], 403);
            }
        }
        
        if ($check['reason'] === 'fresh_passkey_required') {
            // Require fresh Passkey re-authentication
            return response()->json([
                'require_fresh_passkey' => true,
            ]);
        }
        
        // Proceed with operation
        // ...
    }
}
```

### Querying VPN Logins

```php
use App\Models\UserLogin;

// Get all VPN logins for a user
$vpnLogins = UserLogin::forUser($userId)
    ->vpn()
    ->orderBy('logged_in_at', 'desc')
    ->get();

// Get high-risk VPN logins
$highRiskLogins = UserLogin::forUser($userId)
    ->highRisk()
    ->get();

// Get recent VPN logins (last 24 hours)
$recentVpnLogins = UserLogin::forUser($userId)
    ->vpn()
    ->recent()
    ->get();

// Check if device has frequent VPN logins
$device = UserDevice::find($deviceId);
if ($device->hasFrequentVpnLogins(threshold: 3)) {
    // Flag for review
}
```

## OpenAPI Examples

### Login with VPN Detection

```yaml
post:
  summary: User login with VPN detection
  tags:
    - Authentication
  requestBody:
    required: true
    content:
      application/json:
        schema:
          type: object
          properties:
            email:
              type: string
              format: email
            password:
              type: string
            behavioral_signals:
              type: object
              description: Optional behavioral biometrics data
              properties:
                typing:
                  type: object
                mouse:
                  type: object
  responses:
    '200':
      description: Login successful
      content:
        application/json:
          schema:
            type: object
            properties:
              message:
                type: string
              vpn_detected:
                type: boolean
              risk_level:
                type: string
                enum: [low, medium, high, critical]
              protection_applied:
                type: boolean
    '403':
      description: VPN cooldown active
      content:
        application/json:
          schema:
            type: object
            properties:
              error:
                type: string
              cooldown_remaining:
                type: integer
                description: Remaining cooldown time in seconds
```

### Get VPN Login History

```yaml
get:
  summary: Get user VPN login history
  tags:
    - Security
  parameters:
    - name: user_id
      in: path
      required: true
      schema:
        type: integer
    - name: risk_level
      in: query
      schema:
        type: string
        enum: [low, medium, high, critical]
    - name: days
      in: query
      schema:
        type: integer
        default: 30
  responses:
    '200':
      description: VPN login history
      content:
        application/json:
          schema:
            type: object
            properties:
              data:
                type: array
                items:
                  type: object
                  properties:
                    login_id:
                      type: string
                      format: uuid
                    ip_address:
                      type: string
                    is_vpn:
                      type: boolean
                    vpn_provider:
                      type: string
                      nullable: true
                    vpn_risk_level:
                      type: string
                      enum: [low, medium, high, critical]
                    country:
                      type: string
                      nullable: true
                    city:
                      type: string
                      nullable: true
                    is_tor:
                      type: boolean
                    logged_in_at:
                      type: string
                      format: date-time
              meta:
                type: object
                properties:
                  total:
                    type: integer
                  vpn_count:
                    type: integer
                  high_risk_count:
                    type: integer
```

### Check VPN Status

```yaml
get:
  summary: Check current VPN status
  tags:
    - Security
  responses:
    '200':
      description: Current VPN detection result
      content:
        application/json:
          schema:
            type: object
            properties:
              is_vpn:
                type: boolean
              provider:
                type: string
                nullable: true
              risk_level:
                type: string
                enum: [low, medium, high, critical]
              risk_score:
                type: integer
                minimum: 0
                maximum: 100
              detection_sources:
                type: array
                items:
                  type: string
              location:
                type: object
                properties:
                  country:
                    type: string
                  city:
                    type: string
              can_perform_sensitive_operations:
                type: boolean
              requires_fresh_passkey:
                type: boolean
              cooldown_remaining:
                type: integer
                nullable: true
                description: Remaining cooldown time in seconds
```

## Russian Territories Special Rules

The system implements special handling for Russian territories:

- **Territories**: Crimea, Sevastopol, DPR, LPR, Kherson Oblast, Zaporizhzhia Oblast
- **Geo mismatch**: If user location is in these territories but IP shows different country → automatic HIGH risk
- **Registration**: New business registration/KYB from these regions with VPN requires manual review
- **Configuration**: Edit `config/vpn-detection.php`:

```php
'russian_territories' => [
    'enabled' => true,
    'territories' => [
        'Crimea',
        'Sevastopol',
        'Donetsk People\'s Republic',
        'DPR',
        'Luhansk People\'s Republic',
        'LPR',
        'Kherson Oblast',
        'Zaporizhzhia Oblast',
    ],
    'geo_mismatch_to_high' => true,
    'require_manual_review_for_registration' => true,
],
```

## Testing

Run the test suite:

```bash
# Run all VPN detection tests
php artisan test --filter=VpnDetection

# Run specific test file
php artisan test tests/Unit/Security/VpnDetectionServiceTest.php

# Run with coverage
php artisan test --coverage --filter=VpnDetection
```

### Test Coverage

The test suite covers:
- ✅ VPN detection for private IPs (skipped)
- ✅ VPN detection for public IPs
- ✅ Caching of detection results
- ✅ Tor exit node detection
- ✅ Behavioral anomaly integration
- ✅ Risk level calculation
- ✅ Cooldown application
- ✅ Split key invalidation
- ✅ Session termination for critical risk
- ✅ User login record creation
- ✅ Security event creation
- ✅ Device VPN status updates
- ✅ Sensitive operation checks
- ✅ Russian territories geo mismatch

**Coverage target**: ≥95%

## Troubleshooting

### VPN Detection Not Working

1. Check if VPN detection is enabled:
   ```php
   config('vpn-detection.enabled')
   ```

2. Verify provider configuration in `config/vpn-detection.php`

3. Check logs:
   ```bash
   tail -f storage/logs/security.log
   ```

### False Positives on Corporate VPN

1. Add corporate VPN ranges to whitelist in `config/vpn-detection.php`:
   ```php
   'corporate_vpn' => [
       'whitelist' => [
           'ips' => ['10.0.0.0/8', 'your.vpn.range/24'],
           'asns' => [12345],
       ],
   ],
   ```

2. Clear cache:
   ```bash
   php artisan cache:clear
   ```

### High API Costs

1. Enable caching (default: 1 hour TTL):
   ```php
   'cache' => [
       'enabled' => true,
       'ttl_seconds' => 3600,
   ],
   ```

2. Disable unnecessary providers:
   ```php
   'providers' => [
       'maxmind' => ['enabled' => true],  // Local DB, no API cost
       'abuseipdb' => ['enabled' => false], // Disable API-based providers
   ],
   ```

## Security Considerations

### Compliance

- **152-ФЗ**: All medical data anonymized before external LLM calls
- **FSTEC BDU**: VPN detection data stored in immutable audit
- **Russian Territories**: Special handling for Crimea, DPR, LPR, etc.

### Data Privacy

- IP addresses stored in immutable audit logs
- VPN detection data never sent to external LLMs
- Behavioral biometrics hashed with per-user salt

### Rate Limiting

- External API calls rate-limited to 60 requests/minute
- Detection results cached for 1 hour
- Redis-based caching for performance

## Performance

- **Cache TTL**: 1 hour (configurable)
- **Detection latency**: ~50-200ms (with cache hit)
- **Database indexes**: Optimized for VPN-related queries
- **Async protection**: Cooldown and notifications via queues

## Monitoring

### Key Metrics

- VPN detection rate per day
- Risk level distribution
- False positive rate
- Cooldown trigger rate
- API provider response times

### Alerts

- High VPN detection rate spike
- Tor exit node detection surge
- Russian territories geo mismatch alerts
- Provider API failures

## References

- [MaxMind GeoIP2 Documentation](https://dev.maxmind.com/geoip/docs/)
- [AbuseIPDB API](https://abuseipdb.com/api/)
- [Tor Exit Node List](https://check.torproject.org/torbulkexitlist)
- [CatVRF Security Architecture](../security/)

## Support

For issues or questions:
- Create issue in GitHub repository
- Contact security team at security@catvrf.ru
- Check Slack channel #security-vpn-detection

---

**Version**: 1.0.0  
**Last Updated**: 2026-04-23  
**Maintainer**: CatVRF Security Team  
**Compliance**: 152-ФЗ, FSTEC BDU, Russian Territories Regulations
