# Behavioral Biometrics System

**CatVRF 2026 Enterprise Security - Continuous Authentication**

## Overview

The Behavioral Biometrics system provides passive, continuous authentication by analyzing user interaction patterns (keystroke dynamics, mouse movements, touch gestures, and session behavior). This system detects anomalies that may indicate:

- Account takeover (ATO)
- Bot attacks
- Insider threats (staff hunting client data)
- Compromised credentials

## Architecture

### Components

1. **Frontend Collector** (`resources/js/behavioral-collector.js`)
   - Lightweight JavaScript library (~10kb)
   - Collects keystroke, mouse, touch, and session signals
   - Throttled to 50ms intervals
   - Batches signals every 30 seconds

2. **Backend Services**
   - `BehavioralBiometricsCollectorService` - Validates and stores signals
   - `BehavioralBiometricsService` - Orchestrates analysis across modalities
   - `TypingPatternService` - Analyzes keystroke dynamics
   - `MouseDynamicsService` - Analyzes mouse movement patterns
   - `TouchGestureService` - Analyzes touch gestures
   - `MultiModalFusionService` - Fuses multiple biometric signals

3. **Data Models**
   - `BehavioralProfile` - User's aggregate behavioral profile
   - `BehavioralBaseline` - Baseline patterns for comparison
   - `BehavioralSample` - Individual behavioral samples
   - `BehavioralDataPoint` - Raw collected signals (30-day retention)

4. **Async Processing**
   - `BehavioralAnomalyDetectionJob` - Async anomaly analysis
   - Queue-based processing for performance

5. **Integration**
   - **CooldownService** - Automatic cooldown on high-risk anomalies
   - **InsiderThreatService** - Enhanced monitoring for staff roles
   - **FraudControlService** - Behavioral score integrated into fraud detection
   - **ContinuousAuthService** - Step-up authentication on anomalies

## Configuration

All settings in `config/behavioral.php`:

```php
// Enable/disable system
'enabled' => env('BEHAVIORAL_ENABLED', true),

// Consent management
'consent' => [
    'default_enabled' => env('BEHAVIORAL_CONSENT_DEFAULT', true),
    'require_explicit_consent' => env('BEHAVIORAL_REQUIRE_EXPLICIT_CONSENT', false),
],

// Thresholds
'thresholds' => [
    'similarity' => [
        'anomaly' => 0.30,    // Below this = anomaly
        'step_up' => 0.50,    // Below this = require re-auth
        'critical' => 0.15,   // Below this = block action
    ],
],

// Modality weights
'weights' => [
    'typing' => 0.35,
    'mouse' => 0.30,
    'touch' => 0.20,
    'session' => 0.15,
],
```

## Usage

### Frontend Integration

Include the collector script in your layout:

```blade
<script src="{{ asset('js/behavioral-collector.js') }}"></script>
<livewire:behavioral-collector action="login" />
```

Or initialize manually:

```javascript
const collector = new BehavioralCollector({
    throttleMs: 50,
    batchIntervalMs: 30000,
    enabled: true
});

collector.start();

// Override sendBatch to send to your backend
collector.sendBatch = async (action) => {
    const signals = collector.collectSignals(action);
    await fetch('/api/behavioral/collect', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            action: action,
            signals: signals
        })
    });
};

collector.stop();
```

### API Endpoints

All endpoints require authentication (`auth:sanctum`).

#### Collect Signals
```
POST /api/behavioral/collect
```

Request body:
```json
{
  "action": "login|registration|client_search|...",
  "signals": {
    "typing": { "events": [...] },
    "mouse": { "events": [...] },
    "touch": { "events": [...] }
  }
}
```

#### Get Baseline Status
```
GET /api/behavioral/baseline
```

#### Get Session Score
```
GET /api/behavioral/session-score
```

#### Reset Profile (Right to Deletion)
```
DELETE /api/behavioral/profile
```

#### Consent Management
```
PUT /api/behavioral/consent
GET /api/behavioral/consent
```

### Backend Integration

#### Check Behavioral Score in Controllers

```php
use App\Services\Behavioral\BehavioralBiometricsService;

class ClientSearchController extends Controller
{
    public function search(Request $request, BehavioralBiometricsService $behavioral)
    {
        $user = $request->user();
        
        // Check behavioral score for staff
        if ($user->role?->isBusiness()) {
            $result = $behavioral->analyzeSignals(
                user: $user,
                signals: $request->input('behavioral_signals', []),
                sessionId: session()->getId()
            );
            
            if ($result['is_anomalous']) {
                return response()->json([
                    'error' => 'Behavioral anomaly detected',
                    'severity' => $result['anomaly_severity']
                ], 403);
            }
        }
        
        // Proceed with search...
    }
}
```

#### Integrate with Insider Threat Detection

The `InsiderThreatService` already includes behavioral anomaly checking. No additional code needed for basic integration.

## Privacy & Compliance

### 152-ФЗ / GDPR Compliance

**Data Minimization:**
- Only aggregated features stored (mean, std dev, patterns)
- Raw keystrokes NEVER stored
- Raw data points auto-expire after 30 days

**Consent Management:**
- Opt-out model by default (configurable)
- Explicit consent version tracking
- Right to deletion via API endpoint

**Anonymization:**
- IP addresses masked (last octet)
- Device fingerprints hashed (SHA-256)
- No PII in behavioral data

### Privacy Policy Snippet

```
Behavioral Biometrics Data Collection

We use behavioral biometrics to enhance your account security. 
This includes analyzing how you interact with our platform (typing 
rhythm, mouse movements, touch patterns) to detect unauthorized access.

What we collect:
- Aggregated behavioral patterns (typing speed, mouse movement characteristics)
- Session behavior (time spent, navigation patterns)

What we DON'T collect:
- Raw keystrokes or what you type
- Screen content or specific actions
- Personal identification information

Your rights:
- Opt-out anytime in your privacy settings
- Request deletion of your behavioral profile
- Export your behavioral data

Data retention:
- Raw signals: 30 days
- Feature samples: 90 days
- Baseline profiles: 1 year
```

## Performance Impact

- **CPU Overhead:** < 2% per session
- **Network:** ~5KB per batch (every 30s)
- **Storage:** ~1KB per user per day
- **Latency:** < 100ms for analysis

## Security Response

### Anomaly Severity Levels

| Severity | Similarity Score | Action |
|----------|-----------------|--------|
| None | ≥ 0.85 | No action |
| Low | 0.75 - 0.85 | Log only |
| Medium | 0.50 - 0.75 | Alert admin |
| High | 0.30 - 0.50 | Step-up auth (Passkey) |
| Critical | < 0.30 | Block + Cooldown (1-24h) |

### Insider Threat Detection

For staff roles:
- Enhanced weight on behavioral anomalies (1.5x multiplier)
- Additional monitoring for sensitive actions (client data access, financial operations)
- Automatic cooldown on excessive client viewing (>50/hour, >200/day)

## Testing

Run the test suite:

```bash
php artisan test --filter=BehavioralBiometricsTest
```

Test coverage includes:
- Baseline building (typing, mouse, touch)
- Anomaly detection (similar vs different patterns)
- Privacy compliance (no raw keystrokes stored)
- Profile reset (right to deletion)
- API endpoints
- Consent management

## Monitoring

### Key Metrics

- Baseline maturity rate (users with ≥20 samples)
- Anomaly detection rate by severity
- False positive rate
- Analysis latency (P50, P95, P99)
- Queue backlog (if async analysis enabled)

### Grafana Dashboards

See `docs/grafana/catvrf-behavioral-biometrics-dashboard.json` for pre-built dashboards.

## Troubleshooting

### Baseline Not Building

Check:
1. User has consent enabled
2. Minimum samples collected (20 for typing/mouse, 15 for touch)
3. No errors in logs: `tail -f storage/logs/laravel.log | grep behavioral`

### High False Positive Rate

Adjust thresholds in `config/behavioral.php`:
- Increase `thresholds.similarity.anomaly` (default: 0.30)
- Increase `thresholds.similarity.step_up` (default: 0.50)
- Adjust modality weights based on your user base

### Performance Issues

- Enable async analysis: `BEHAVIORAL_ASYNC_ANALYSIS=true`
- Increase throttle interval: `BEHAVIORAL_THROTTLE_MS=100`
- Reduce batch frequency: `BEHAVIORAL_BATCH_INTERVAL=60000`

## Migration from Legacy

If migrating from an older system:

1. Run migrations: `php artisan migrate`
2. Import existing baselines via `BehavioralBaseline::create()`
3. Update frontend to use new collector
4. Gradual rollout: enable per-user with feature flag

## References

- [NIST Digital Identity Guidelines](https://pages.nist.gov/800-63-3/)
- [GDPR Article 25 - Data Protection by Design](https://gdpr-info.eu/art-25-gdpr/)
- [152-ФЗ - Personal Data Law](https://consultant.ru/document/cons_doc_LAW_61801/)

## Support

For issues or questions:
- Check logs in `storage/logs/laravel.log`
- Review metrics in Grafana
- Contact security team via internal Slack #security-behavioral

---

**Version:** 1.0.0  
**Last Updated:** 2026-04-23  
**Maintained By:** Security Team  
**Compliance:** 152-ФЗ, GDPR compliant
