# Split Key Implementation Guide

## Overview

Split Key is a hybrid cryptographic security feature for CatVRF that provides enhanced protection against device cloning, MITM attacks, and token substitution attacks. It complements Passkeys and Behavioral Biometrics to create a defense-in-depth security posture.

## Architecture

### Key Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Client Device                           │
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │  Secure Enclave  │         │  Web Crypto API  │         │
│  │  / TPM / Keystore│         │                  │         │
│  │  ┌────────────┐  │         │  ┌────────────┐  │         │
│  │  │Device Part │  │         │  │ Challenge  │  │         │
│  │  │(never sent) │  │         │  │ Generation │  │         │
│  │  └────────────┘  │         │  └────────────┘  │         │
│  └──────────────────┘         └──────────────────┘         │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP (TLS)
                              │
┌─────────────────────────────────────────────────────────────┐
│                     Server (CatVRF)                         │
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │  Server Part    │         │  SplitKeyService │         │
│  │  (encrypted)     │◄────────┤                  │         │
│  │  ┌────────────┐  │         │  ┌────────────┐  │         │
│  │  │AES-256     │  │         │  │ Full Key   │  │         │
│  │  │Encrypted   │  │         │  │ XOR/HMAC   │  │         │
│  │  └────────────┘  │         │  └────────────┘  │         │
│  └──────────────────┘         └──────────────────┘         │
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │  FraudControl    │         │  Behavioral      │         │
│  │  Service         │────────►│  Biometrics      │         │
│  └──────────────────┘         └──────────────────┘         │
└─────────────────────────────────────────────────────────────┘
```

### Key Lifecycle

1. **Generation**: After successful Passkey authentication
   - Server generates cryptographically secure random Server Part (256 bits)
   - Device generates Device Part in Secure Enclave/TPM
   - Full Key = Server Part ⊕ Device Part (XOR or HMAC-based)
   - Server stores only encrypted Server Part + hash of Full Key
   - Device Part never leaves the device

2. **Validation**: Challenge-response for sensitive operations
   - Server sends nonce challenge
   - Device signs with Full Key
   - Server verifies signature
   - Updates activity timestamp

3. **Expiration**: 7-day activity window
   - Key expires 7 days after last activity
   - Automatic rotation on next login
   - Manual rotation supported

4. **Invalidation**: On risk detection
   - High fraud score (> 0.7)
   - Behavioral anomaly (critical/high severity)
   - Insider threat detection
   - Manual revocation

## Database Schema

### split_keys Table

```sql
CREATE TABLE split_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    tenant_id BIGINT UNSIGNED NULL,
    server_part_encrypted TEXT NOT NULL,
    key_hash VARCHAR(64) UNIQUE NOT NULL,
    last_activity_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    status VARCHAR(20) DEFAULT 'active',
    attestation_data JSON NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_user_status_expires (user_id, status, expires_at),
    INDEX idx_tenant_status_expires (tenant_id, status, expires_at),
    INDEX idx_activity_status (last_activity_at, status),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

## API Endpoints

### Generate Challenge

```http
GET /api/split-key/challenge
Authorization: Bearer {token}
```

**Response:**
```json
{
  "challenge": "base64_encoded_challenge",
  "challenge_id": "uuid"
}
```

### Validate Split Key

```http
POST /api/split-key/validate
Authorization: Bearer {token}
X-Split-Key-Challenge: {challenge}
X-Split-Key-Signature: {signature}
X-Correlation-ID: {correlation_id}
```

**Response:**
```json
{
  "valid": true,
  "split_key": {
    "id": 1,
    "expires_at": "2026-04-30T12:00:00Z"
  }
}
```

## Service Usage

### Generate Split Key

```php
use App\DTO\SplitKey\GenerateSplitKeyDTO;
use App\Services\Security\SplitKeyService;

$dto = new GenerateSplitKeyDTO(
    userId: $user->id,
    tenantId: $tenantId,
    serverPart: random_bytes(32),
    deviceAttestation: $attestationData,
    ipAddress: $request->ip(),
    userAgent: $request->userAgent(),
    correlationId: $correlationId,
);

$splitKey = $splitKeyService->generate($dto);
```

### Validate Split Key

```php
use App\DTO\SplitKey\ValidateSplitKeyDTO;

$dto = new ValidateSplitKeyDTO(
    userId: $user->id,
    tenantId: $tenantId,
    challenge: $challenge,
    signature: $signature,
    ipAddress: $request->ip(),
    correlationId: $correlationId,
);

$result = $splitKeyService->validateAndUse($dto);

if (!$result['valid']) {
    throw new \Exception($result['error']);
}
```

### Invalidate on Risk

```php
use App\DTO\SplitKey\InvalidateSplitKeyDTO;

$dto = new InvalidateSplitKeyDTO(
    userId: $user->id,
    tenantId: $tenantId,
    reason: 'High fraud score detected',
    riskLevel: 'high',
    source: 'fraud',
    ipAddress: $request->ip(),
    correlationId: $correlationId,
);

$splitKeyService->invalidateOnRisk($dto);
```

## Middleware Configuration

### Apply to Sensitive Routes

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'split-key'])
    ->group(function () {
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/withdrawals', [WithdrawalController::class, 'store']);
        Route::post('/transfers', [TransferController::class, 'store']);
    });
```

### Register Middleware

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    // ...
    'split-key' => \App\Http\Middleware\SplitKeyValidationMiddleware::class,
];
```

## Events and Listeners

### Events

- **SplitKeyGenerated**: Dispatched when a new split key is generated
- **SplitKeyInvalidated**: Dispatched when a split key is invalidated
- **SplitKeyRotated**: Dispatched when a split key is rotated

### Listeners

- **InvalidateSplitKeyOnHighRisk**: Listens for high-risk events and invalidates split keys

## Integration Points

### WebAuthn Authentication

Split keys are automatically generated after successful Passkey authentication in `WebAuthnAuthenticationService::authenticate()`.

### Fraud Detection

High fraud scores (> 0.7) trigger automatic split key invalidation via `InvalidateSplitKeyOnHighRisk` listener.

### Behavioral Biometrics

Critical or high severity behavioral anomalies trigger automatic split key invalidation.

### Cooldown System

Critical risk invalidations trigger cooldown periods (24-72 hours) requiring fresh Passkey + liveness.

## Security Considerations

### Production Requirements

1. **Server Part Encryption**: Must use AES-256 with application key
2. **Device Attestation**: Must verify Secure Enclave/TPM attestation
3. **Signature Verification**: Full ECDSA/EdDSA signature verification
4. **Challenge Freshness**: 5-minute TTL, single-use
5. **Audit Logging**: All operations logged to ClickHouse
6. **Rate Limiting**: Prevent brute force on signature verification

### Threat Mitigations

| Threat | Mitigation |
|--------|------------|
| Device Cloning | Device attestation + hardware binding |
| MITM | Challenge-response + TLS |
| Token Substitution | Signature verification + short TTL |
| Replay Attack | Nonce challenges + counter tracking |
| Key Extraction | Secure Enclave/TPM + never send plaintext |

## Testing

### Run Tests

```bash
php artisan test tests/Unit/Services/Security/SplitKeyServiceTest.php
```

### Test Coverage

- ✅ Split key generation
- ✅ Old key revocation on new generation
- ✅ Valid signature validation
- ✅ Invalid signature rejection
- ✅ No active key handling
- ✅ Risk-based invalidation
- ✅ 7-day inactivity rotation
- ✅ Challenge generation
- ✅ Tenant-aware keys
- ✅ Expiration handling
- ✅ Cloning simulation

## Monitoring

### Key Metrics

- Split key generation rate
- Split key validation success rate
- Split key invalidation rate (by reason)
- Time to rotation after inactivity
- Challenge-response latency

### Alerting

- High invalidation rate (> 5% of validations)
- Failed attestation rate (> 1%)
- Cloning attempt detection

## Deployment Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Register middleware in Kernel.php
- [ ] Add middleware to sensitive routes
- [ ] Configure event listeners in EventServiceProvider
- [ ] Run tests: `php artisan test`
- [ ] Monitor logs for 24 hours
- [ ] Enable in production after validation

## Troubleshooting

### Common Issues

**Issue**: Split key validation fails
- Check: Challenge is fresh (within 5 minutes)
- Check: Signature length >= 64 bytes
- Check: User has active split key
- Check: Server part encryption is working

**Issue**: Split key not generated after login
- Check: WebAuthn authentication succeeds
- Check: SplitKeyService is injected
- Check: Database connection
- Check: Encryption key is configured

**Issue**: Keys invalidated unexpectedly
- Check: Fraud score in logs
- Check: Behavioral anomaly severity
- Check: Insider threat detection
- Review: Manual invalidation logs

## References

- [CatVRF Security Architecture](../security/README.md)
- [Passkeys Implementation](./PASSKEYS.md)
- [Behavioral Biometrics](./BEHAVIORAL_BIOMETRICS.md)
- [Fraud Detection](./FRAUD_DETECTION.md)

---

**Version**: 1.0  
**Last Updated**: 2026-04-23  
**Status**: Production Ready  
**Compliance**: 152-ФЗ, ФЗ-323, FSTEC BDU
