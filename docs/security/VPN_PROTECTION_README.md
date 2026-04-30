# VPN Protection System - Intelligent Risk-Based Restrictions

## Overview

The VPN Protection System implements an intelligent, risk-based approach to VPN/Proxy detection and restriction for CatVRF. **VPN/Proxy alone does NOT block business operations** - only when combined with additional fraud/ML/behavioral risk signals.

## Key Principles

1. **No False Positives**: Users using VPN for legitimate purposes (accessing blocked foreign services) are not blocked
2. **Targeted Restrictions**: Blocks only apply to high-risk actions (withdrawals, bank changes, KYB, staff invitations)
3. **Gradient Protection**: Risk levels (Low/Medium/High/Critical) determine restriction severity
4. **Russian Territories**: Special handling for Crimea, Sevastopol, DPR, LPR, Kherson, Zaporizhzhia

## Architecture

### Components

1. **VpnDetectionService** - Detects VPN usage with multi-layered IP intelligence
2. **ResidentialProxyDetectionService** - Detects residential proxy usage
3. **VpnProtectionMiddleware** - Middleware for automatic protection on routes
4. **ChecksVpnProtection Trait** - Trait for manual protection in controllers/services
5. **Config** - `config/vpn-protection.php` with thresholds and rules

### Risk Factors

A block is applied only when VPN/Proxy is detected **PLUS** at least one of:

- **Fraud score ≥ 0.65** (from FraudControlService)
- **Behavioral anomaly** (score < 0.75 from BehavioralBiometricsService)
- **Insider threat** (score ≥ 0.6 from InsiderThreatService)
- **Mass actions** (≥15 actions in 5 minutes)
- **Russian territory mismatch** (claimed region ≠ detected IP country)

### Protection Levels

| Level | Cooldown | Blocks Financial | Blocks Critical | Passkey | Liveness |
|-------|----------|------------------|-----------------|---------|----------|
| Low   | 0h       | No               | No              | No      | No       |
| Medium| 0h       | No               | No              | No      | No       |
| High  | 24h      | Yes              | Yes             | No      | No       |
| Critical| 72h    | Yes              | Yes             | Yes     | Yes      |

## Usage

### Middleware (Automatic)

Apply to routes in `routes/api.php`:

```php
Route::middleware(['auth', 'VpnProtectionMiddleware'])
    ->group(function () {
        Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);
        Route::post('tenant/bank-details/update', [TenantController::class, 'updateBankDetails']);
        Route::post('kyb/submit', [KybController::class, 'submit']);
        Route::post('staff/invite', [StaffController::class, 'invite']);
    });
```

### Trait (Manual)

Use in controllers/services for granular control:

```php
use App\Traits\ChecksVpnProtection;

class WalletController extends Controller
{
    use ChecksVpnProtection;
    
    public function withdraw(Request $request)
    {
        $check = $this->checkVpnProtection($request, $user, 'financial');
        if ($check['should_block']) {
            return response()->json(['error' => $check['message']], 403);
        }
        
        // Proceed with withdrawal
    }
}
```

## Configuration

Edit `config/vpn-protection.php`:

```php
'thresholds' => [
    'fraud_score' => 0.65,
    'behavioral_score' => 0.75,
    'insider_threat_score' => 0.6,
    'mass_actions' => [
        'count' => 15,
        'window_minutes' => 5,
    ],
],

'high_risk_actions' => [
    'financial' => [
        'wallet.withdraw',
        'wallet.transfer',
        'payment.process',
    ],
    'critical_changes' => [
        'tenant.settings.update',
        'tenant.bank_details.update',
        'kyb.submit',
        'staff.invite',
    ],
],
```

## Database Migration

Run migration to add VPN protection columns to `user_devices`:

```bash
php artisan migrate
```

New columns:
- `vpn_protection_risk_level` - Risk level (low/medium/high/critical)
- `vpn_protection_risk_factors` - JSON array of detected risk factors
- `vpn_protection_block_applied` - Whether block was applied
- `vpn_protection_blocked_at` - When block was applied
- `vpn_protection_block_expires_at` - When block expires
- `vpn_protection_correlation_id` - Correlation ID for tracking

## ClickHouse Logging

VPN protection events are logged to ClickHouse table `vpn_protection_events`:

```sql
CREATE TABLE vpn_protection_events (
    timestamp DateTime,
    user_id UInt64,
    tenant_id UInt64,
    action_type String,
    risk_level String,
    risk_factors String,
    ip_address String,
    correlation_id String,
    should_block Bool
) ENGINE = MergeTree()
ORDER BY (timestamp, user_id);
```

## Testing

Run Pest tests:

```bash
php artisan test --filter=VpnProtectionTest
```

Test coverage includes:
- VPN detection without risk factors → no block
- VPN + fraud score ≥ 0.65 → 24h block
- Residential proxy + behavioral anomaly → 72h block
- Russian territory mismatch → auto high risk
- Mass actions trigger → block applied

## Monitoring

Monitor VPN protection events via:

1. **Filament Admin** - View VPN protection status in Device Management
2. **ClickHouse** - Query `vpn_protection_events` table
3. **Security Logs** - Channel `security` logs all blocks
4. **Prometheus** - Metrics for blocked actions (if configured)

## Troubleshooting

### Legitimate users blocked

If legitimate users are blocked:
1. Check `vpn_protection_risk_factors` in `user_devices` table
2. Verify fraud/behavioral scores are correct
3. Consider adjusting thresholds in `config/vpn-protection.php`
4. Use manual override via admin panel if needed

### High false positive rate

Reduce thresholds:
```php
'thresholds' => [
    'fraud_score' => 0.75,  // Increased from 0.65
    'behavioral_score' => 0.65,  // Decreased from 0.75
],
```

### Russian territory issues

Disable special handling if needed:
```php
'russian_territories' => [
    'enabled' => false,
],
```

## Security Compliance

- **152-ФЗ**: All VPN detections are logged with correlation IDs
- **ФЗ-323**: Medical data is never sent to external LLMs with VPN info
- **GDPR**: IP addresses are masked in logs after 90 days
- **Audit Trail**: Full audit trail in ClickHouse for compliance

## API Documentation

See OpenAPI spec for detailed endpoint documentation:

```yaml
/vpn-protection/check:
  post:
    summary: Check VPN protection status
    responses:
      200:
        description: No block applied
      403:
        description: Block applied with details
```

## Support

For issues or questions:
- Check security logs: `storage/logs/security.log`
- Review ClickHouse events
- Contact security team with correlation ID
