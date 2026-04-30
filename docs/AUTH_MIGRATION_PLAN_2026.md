# CatVRF Authentication Migration Plan 2026
## Migration Strategy for Existing Users

**Date:** April 19, 2026  
**Purpose:** Migrate existing users to modern authentication methods (Passkeys, DID/VC, AI Agent Auth)

---

## Executive Summary

**Current State:** CatVRF has world-class authentication stack with Passkeys (FIDO2 Level 3), Continuous Auth, Behavioral Biometrics, and Voice Biometrics. Most authentication methods are already production-ready.

**Migration Required:** Minimal. The primary migration needed is for any legacy password-based users to Passkeys. Future migrations will be for DID/VC and AI Agent Auth when those features are implemented.

**Migration Strategy:** Phased, opt-in approach with gradual enforcement.

---

## Current Authentication State

### Already Implemented ✅
- **Passkeys (WebAuthn/FIDO2 Level 3)** - Production Ready
- **Continuous Authentication** - Production Ready
- **Behavioral Biometrics** - Production Ready
- **Voice Biometrics** - Production Ready
- **Liveness Check** - Production Ready

### To Be Implemented ❌
- **DID/VC Support** - Planned (6-8 weeks)
- **AI Agent Auth** - Planned (2-3 weeks)
- **Fallback Methods** (Voice OTP, Hardware Keys, Magic Links) - Planned (1 week)

---

## Phase 1: Passkey Migration (Immediate)

### Objective
Migrate any existing password-based users to Passkeys for enhanced security.

### Pre-Migration Assessment
```bash
# Query to identify users with password auth only
SELECT COUNT(*) FROM users WHERE password IS NOT NULL AND webauthn_credentials_count = 0;
```

### Migration Strategy

#### Option A: Opt-In Migration (Recommended)
- **Timeline:** 2-4 weeks
- **Approach:** Encourage users to add Passkeys via in-app prompts
- **Benefits:** User-friendly, low friction
- **Risks:** Slow adoption rate

**Implementation:**
1. Add "Add Passkey" banner in user dashboard
2. Send email campaign highlighting Passkey benefits
3. Offer incentives (e.g., 5% discount on next transaction)
4. Monitor adoption rate weekly

#### Option B: Soft Enforcement
- **Timeline:** 4-6 weeks
- **Approach:** Require Passkey for sensitive operations
- **Benefits:** Faster adoption
- **Risks:** Higher friction

**Implementation:**
1. Apply `RequirePasskey` middleware to sensitive routes:
   - `/api/withdrawal/*`
   - `/api/business/*`
   - `/api/admin/*`
2. Show "Add Passkey to continue" modal
3. Allow password as fallback during grace period
4. Enforce Passkey-only after grace period

#### Option C: Hard Enforcement (Not Recommended)
- **Timeline:** 8-12 weeks
- **Approach:** Force all users to add Passkeys
- **Benefits:** 100% adoption
- **Risks:** High churn, user dissatisfaction

**Implementation:**
1. Disable password login
2. Show "Add Passkey required" modal
3. Provide support for users without Passkey-capable devices
4. Fallback to Voice OTP/Magic Links

### Recommended Approach: Option A + Option B Hybrid
- Week 1-2: Opt-in campaign with incentives
- Week 3-4: Soft enforcement for sensitive operations
- Week 5-6: Monitor and adjust based on adoption

### Migration Steps

#### Step 1: Preparation (Week 1)
1. **Backup existing credentials**
```php
// Backup password hashes (in case of rollback)
php artisan auth:backup-passwords
```

2. **Add migration tracking**
```php
// Migration: 2026_04_19_000003_add_auth_migration_tracking.php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('passkey_migration_completed')->default(false);
    $table->timestamp('passkey_migration_at')->nullable();
    $table->string('auth_method')->default('password'); // password, passkey, did_vc
});
```

3. **Create migration dashboard in Filament**
- Track migration progress by tenant
- Show adoption rate over time
- Identify users needing support

#### Step 2: Opt-In Campaign (Week 1-2)
1. **Add in-app banner**
```vue
<!-- resources/js/Components/Auth/PasskeyPromptBanner.vue -->
<template>
  <div v-if="shouldShowPrompt" class="bg-blue-600 text-white p-4 rounded-lg">
    <h3>🔐 Enable Passkeys for Enhanced Security</h3>
    <p>Add a Passkey to login with Face ID or fingerprint - no passwords needed!</p>
    <button @click="showPasskeyRegistration">Add Passkey Now</button>
  </div>
</template>
```

2. **Send email campaign**
```php
// App/Mail/PasskeyMigrationPrompt.php
class PasskeyMigrationPrompt extends Mailable
{
    public function build()
    {
        return $this->markdown('emails.auth.passkey-migration-prompt')
            ->subject('🔐 Upgrade your security with Passkeys');
    }
}
```

3. **Offer incentives**
- 5% discount on next transaction
- Free priority support for 30 days
- Early access to new features

#### Step 3: Soft Enforcement (Week 3-4)
1. **Apply RequirePasskey middleware**
```php
// routes/api.php
Route::middleware(['auth', 'RequirePasskey'])->group(function () {
    Route::prefix('withdrawal')->group(...);
    Route::prefix('business')->group(...);
    Route::prefix('admin')->group(...);
});
```

2. **Add grace period logic**
```php
// app/Http/Middleware/RequirePasskey.php
public function handle($request, Closure $next)
{
    $user = $request->user();
    
    if ($user->hasPasskey() || $user->inGracePeriod()) {
        return $next($request);
    }
    
    return response()->json([
        'error' => 'passkey_required',
        'message' => 'Please add a Passkey to continue',
        'action' => 'redirect_to_passkey_setup'
    ], 403);
}
```

3. **Show setup modal**
```vue
<!-- resources/js/Components/Auth/PasskeyRequiredModal.vue -->
<template>
  <Modal v-if="showModal">
    <h2>Passkey Required</h2>
    <p>This operation requires a Passkey for enhanced security.</p>
    <button @click="setupPasskey">Setup Passkey</button>
    <button @click="useFallback">Use Voice OTP (Fallback)</button>
  </Modal>
</template>
```

#### Step 4: Monitoring & Support (Week 5-6)
1. **Track migration metrics**
```php
// App/Services/Auth/AuthMigrationTrackingService.php
class AuthMigrationTrackingService
{
    public function getMigrationStats(): array
    {
        return [
            'total_users' => User::count(),
            'passkey_users' => User::whereHas('webauthnCredentials')->count(),
            'adoption_rate' => $this->calculateAdoptionRate(),
            'by_tenant' => $this->getAdoptionByTenant(),
        ];
    }
}
```

2. **Provide support for edge cases**
- Users without Passkey-capable devices → Voice OTP
- Users with accessibility needs → Hardware keys
- Users in restricted regions → Magic links

#### Step 5: Post-Migration (Week 6+)
1. **Deprecate password auth** (optional)
```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'passkey', // Change from 'session'
        // ...
    ],
],
```

2. **Clean up legacy data**
```bash
# Remove password hashes after 90 days
php artisan auth:cleanup-passwords --days=90
```

### Rollback Plan
```bash
# If migration fails, rollback to password auth
php artisan migrate:rollback --step=1
php artisan auth:restore-passwords
```

---

## Phase 2: DID/VC Migration (Future - After Implementation)

### Objective
Migrate users/businesses to DID/VC for portable identity and cross-tenant trust.

### Timing
- **Start:** After DID/VC implementation (Week 8-10 of Phase 2 in gaps analysis)
- **Duration:** 8-12 weeks

### Migration Strategy

#### Step 1: DID Generation (Week 1-2)
1. **Generate DIDs for existing entities**
```php
// App/Services/DID/DIDMigrationService.php
class DIDMigrationService
{
    public function generateDIDsForExistingUsers(): void
    {
        User::whereNull('did')->chunk(1000, function ($users) {
            foreach ($users as $user) {
                $did = $this->didService->generateDID($user);
                $user->update(['did' => $did]);
            }
        });
    }
}
```

2. **Add DID to user/business records**
```php
// Migration: 2026_06_01_000001_add_did_to_users.php
Schema::table('users', function (Blueprint $table) {
    $table->string('did')->nullable()->unique();
    $table->string('did_document')->nullable(); // IPFS hash
});
```

#### Step 2: VC Issuance (Week 3-4)
1. **Issue KYB completion VCs for verified businesses**
```php
// App/Services/VC/VCIssuanceService.php
class VCIssuanceService
{
    public function issueKYBCompletionVC(Business $business): VerifiableCredential
    {
        $vc = [
            '@context' => ['https://www.w3.org/2018/credentials/v1'],
            'type' => ['VerifiableCredential', 'KYBCompletionCredential'],
            'issuer' => config('did.issuer_did'),
            'issuanceDate' => now()->toISOString(),
            'credentialSubject' => [
                'id' => $business->did,
                'inn' => $business->inn,
                'company_name' => $business->name,
                'kyb_status' => 'verified',
                'verified_at' => $business->kyb_verified_at,
            ],
        ];
        
        return $this->signVC($vc);
    }
}
```

2. **Issue identity VCs for verified individuals**
```php
public function issueIdentityVC(User $user): VerifiableCredential
{
    $vc = [
        '@context' => ['https://www.w3.org/2018/credentials/v1'],
        'type' => ['VerifiableCredential', 'IdentityCredential'],
        'issuer' => config('did.issuer_did'),
        'issuanceDate' => now()->toISOString(),
        'credentialSubject' => [
            'id' => $user->did,
            'name' => $user->name,
            'verified' => $user->identity_verified,
        ],
    ];
    
    return $this->signVC($vc);
}
```

#### Step 3: VC Verification Integration (Week 5-6)
1. **Update KYB flow to accept VCs**
```php
// App/Services/KYB/KYBService.php
public function startVerificationWithVC(string $inn, VerifiableCredential $vc): KYBVerification
{
    // Verify VC signature and validity
    if (!$this->vcService->verifyVC($vc)) {
        throw new InvalidVCException('Invalid Verifiable Credential');
    }
    
    // Skip UBO extraction if VC contains verified data
    if ($vc->hasUBOData()) {
        return $this->createFromVC($vc);
    }
    
    // Fall back to standard KYB flow
    return $this->startVerification($inn);
}
```

2. **Add VC verification API endpoints**
```php
// routes/api/did.php
Route::post('/vc/verify', [DIDAuthController::class, 'verifyVC']);
Route::post('/vc/present', [DIDAuthController::class, 'presentVC']);
```

#### Step 4: Cross-Tenant Trust (Week 7-8)
1. **Enable VC-based trust between tenants**
```php
// App/Services/DID/VCTrustService.php
class VCTrustService
{
    public function establishTrust(string $tenantA, string $tenantB): void
    {
        // Exchange trust anchors
        $trustAnchorA = $this->getTrustAnchor($tenantA);
        $trustAnchorB = $this->getTrustAnchor($tenantB);
        
        // Store in trust registry
        $this->trustRegistry->register($tenantA, $trustAnchorB);
        $this->trustRegistry->register($tenantB, $trustAnchorA);
    }
}
```

2. **Enable VC-based onboarding for franchise branches**
```php
// Franchise onboarding with VC
public function onboardFranchiseBranch(Business $parent, array $branchData): Business
{
    // Issue VC for parent business
    $parentVC = $this->vcService->issueBusinessVC($parent);
    
    // Branch presents parent VC for verification
    $branch = $this->onboardingService->onboardWithVC($branchData, $parentVC);
    
    return $branch;
}
```

#### Step 5: User Migration to DID (Week 9-12)
1. **Opt-in DID activation for users**
```php
// User activates DID via dashboard
Route::post('/api/did/activate', [DIDAuthController::class, 'activateDID']);
```

2. **Gradual DID enforcement**
- Phase 1: DID for cross-tenant operations (Week 9-10)
- Phase 2: DID for B2B operations (Week 11-12)
- Phase 3: DID as primary identity (future)

---

## Phase 3: AI Agent Auth Migration (Future - After Implementation)

### Objective
Migrate AI agents and services to OAuth2 + mTLS authentication.

### Timing
- **Start:** After AI Agent Auth implementation (Week 7 of Phase 2 in gaps analysis)
- **Duration:** 2-3 weeks

### Migration Strategy

#### Step 1: OAuth2 Server Setup (Week 1)
1. **Deploy OAuth2 authorization server**
```bash
# Install League OAuth2 Server
composer require league/oauth2-server
```

2. **Register existing services as OAuth2 clients**
```php
// DatabaseSeeder
OAuth2Client::create([
    'name' => 'Inventory Agent',
    'client_id' => Str::random(40),
    'client_secret' => Str::random(60),
    'scopes' => ['inventory:read', 'inventory:write'],
    'grant_types' => ['client_credentials'],
]);
```

#### Step 2: mTLS Certificate Issuance (Week 1-2)
1. **Generate certificates for services**
```bash
# Generate CA
openssl genrsa -out ca.key 4096
openssl req -new -x509 -days 365 -key ca.key -out ca.crt

# Generate service certificates
openssl genrsa -out inventory-agent.key 4096
openssl req -new -key inventory-agent.key -out inventory-agent.csr
openssl x509 -req -days 365 -in inventory-agent.csr -CA ca.crt -CAkey ca.key -CAcreateserial -out inventory-agent.crt
```

2. **Deploy certificates to services**
```env
# .env
MTLS_CERT=/etc/certs/inventory-agent.crt
MTLS_KEY=/etc/certs/inventory-agent.key
MTLS_CA=/etc/certs/ca.crt
```

#### Step 3: Service Authentication Update (Week 2-3)
1. **Update services to use OAuth2 + mTLS**
```php
// App/Services/Inventory/InventoryAgentService.php
class InventoryAgentService
{
    public function authenticate(): string
    {
        // Get OAuth2 token via client credentials
        $token = $this->oauth2Service->getClientCredentialsToken(
            clientId: config('services.inventory.client_id'),
            clientSecret: config('services.inventory.client_secret'),
            scopes: ['inventory:read', 'inventory:write']
        );
        
        return $token;
    }
}
```

2. **Add mTLS middleware to internal APIs**
```php
// app/Http/Middleware/RequireMTLS.php
public function handle($request, Closure $next)
{
    $cert = $request->server('SSL_CLIENT_CERT');
    
    if (!$cert || !$this->validateCertificate($cert)) {
        return response()->json(['error' => 'invalid_certificate'], 403);
    }
    
    return $next($request);
}
```

#### Step 4: Legacy Deprecation (Week 3)
1. **Deprecate API key authentication**
```php
// Mark API keys as deprecated
ApiKey::query()->update(['deprecated' => true]);
```

2. **Monitor token usage**
```php
// Track OAuth2 token usage
Route::middleware(['auth:oauth2'])->group(function () {
    // Log token usage for audit
});
```

---

## Phase 4: Fallback Methods Migration (Future - After Implementation)

### Objective
Ensure users without Passkey-capable devices can still authenticate.

### Timing
- **Start:** After fallback methods implementation (Week 11 of Phase 3 in gaps analysis)
- **Duration:** 1 week

### Migration Strategy

#### Step 1: Voice OTP Setup (2-3 days)
1. **Configure voice OTP provider**
```php
// config/voice-otp.php
return [
    'provider' => 'twilio', // or nexmo, plivo
    'api_key' => env('VOICE_OTP_API_KEY'),
    'phone_number' => env('VOICE_OTP_PHONE_NUMBER'),
];
```

2. **Add Voice OTP as fallback in auth flow**
```php
// app/Http/Controllers/Auth/AuthController.php
public function loginWithFallback(Request $request)
{
    try {
        return $this->webAuthnService->authenticate($request);
    } catch (WebAuthnException $e) {
        // Fallback to Voice OTP
        return $this->voiceOTPService->sendOTP($request->user()->phone);
    }
}
```

#### Step 2: Hardware Key Setup (2-3 days)
1. **Enable YubiKey support for privileged users**
```php
// Update WebAuthn service to support security keys
public function registerSecurityKey(Request $request)
{
    // Same as Passkey registration, but with attestation = 'indirect'
    $attestation = WebAuthn::create(
        user: $request->user(),
        attestation: 'indirect', // Allow security keys
        authenticatorSelection: [
            'authenticatorAttachment' => 'cross-platform',
        ]
    );
}
```

2. **Require hardware keys for admin accounts**
```php
// Apply to admin routes
Route::middleware(['auth', 'RequireHardwareKey'])->prefix('admin')->group(...);
```

#### Step 3: Magic Link Setup (1-2 days)
1. **Configure magic link provider**
```php
// config/magic-link.php
return [
    'expiration' => 300, // 5 minutes
    'token_length' => 32,
];
```

2. **Add magic link as last resort**
```php
// Send magic link
Route::post('/auth/magic-link/send', [AuthController::class, 'sendMagicLink']);

// Verify magic link
Route::get('/auth/magic-link/verify/{token}', [AuthController::class, 'verifyMagicLink']);
```

---

## Monitoring & Metrics

### Key Metrics to Track

#### Phase 1 (Passkey Migration)
- Passkey adoption rate
- Password login attempts (should decrease)
- Support tickets related to migration
- Time to add Passkey (median)
- Failed Passkey registrations

#### Phase 2 (DID/VC Migration)
- DID generation rate
- VC issuance rate
- VC verification success rate
- Cross-tenant trust establishment rate
- VC-based onboarding time

#### Phase 3 (AI Agent Auth)
- OAuth2 token issuance rate
- mTLS certificate validity
- API key deprecation progress
- Service authentication failures

#### Phase 4 (Fallback Methods)
- Voice OTP usage rate
- Hardware key usage rate
- Magic link usage rate
- Fallback method success rate

### Dashboard Queries

```sql
-- Passkey adoption rate
SELECT 
    DATE(passkey_migration_at) as date,
    COUNT(*) as migrated_users,
    (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users)) as adoption_rate
FROM users 
WHERE passkey_migration_at IS NOT NULL
GROUP BY DATE(passkey_migration_at)
ORDER BY date;

-- DID generation progress
SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN did IS NOT NULL THEN 1 ELSE 0 END) as did_users,
    (SUM(CASE WHEN did IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as did_adoption_rate
FROM users;

-- OAuth2 token usage
SELECT 
    DATE(created_at) as date,
    COUNT(*) as tokens_issued,
    COUNT(DISTINCT client_id) as active_clients
FROM oauth2_access_tokens
GROUP BY DATE(created_at)
ORDER BY date;
```

---

## Risk Mitigation

### Phase 1 Risks
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Low adoption rate | Medium | Medium | Incentives, soft enforcement |
| User churn | Low | High | Grace period, support |
| Device incompatibility | Low | Medium | Voice OTP fallback |

### Phase 2 Risks
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| DID generation failure | Low | High | Rollback to existing auth |
| VC verification failure | Low | Medium | Fallback to standard KYB |
| Cross-tenant trust issues | Low | Medium | Manual review queue |

### Phase 3 Risks
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| OAuth2 server downtime | Low | High | Fallback to API keys |
| mTLS certificate expiry | Medium | Medium | Auto-renewal, monitoring |
| Service authentication failure | Low | High | Circuit breaker, retry logic |

### Phase 4 Risks
| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Voice OTP cost overruns | Medium | Low | Rate limiting, monitoring |
| Hardware key distribution | Low | Medium | Ship to privileged users only |
| Magic link abuse | Medium | Low | Rate limiting, one-time use |

---

## Rollback Plans

### Phase 1 Rollback
```bash
# Revert to password auth
php artisan migrate:rollback --step=1
php artisan auth:restore-passwords

# Disable Passkey requirement
# config/passkey.php -> 'required' => false
```

### Phase 2 Rollback
```php
// Disable DID/VC verification
// config/did.php -> 'enabled' => false

// Revert to standard KYB flow
// KYBService.php -> use startVerification() instead of startVerificationWithVC()
```

### Phase 3 Rollback
```bash
# Re-enable API keys
ApiKey::query()->update(['deprecated' => false]);

# Disable mTLS requirement
# Remove RequireMTLS middleware from routes
```

### Phase 4 Rollback
```php
// Disable fallback methods
// config/voice-otp.php -> 'enabled' => false
// config/magic-link.php -> 'enabled' => false
```

---

## Timeline Summary

| Phase | Duration | Start | End | Status |
|-------|----------|-------|-----|--------|
| Phase 1: Passkey Migration | 6 weeks | Immediate | Week 6 | READY TO START |
| Phase 2: DID/VC Migration | 12 weeks | Week 8-10 | Week 20-22 | PENDING |
| Phase 3: AI Agent Auth | 3 weeks | Week 7-9 | Week 10-11 | PENDING |
| Phase 4: Fallback Methods | 1 week | Week 11 | Week 12 | PENDING |

**Total Duration:** 6 weeks (Phase 1 only, immediate)  
**Total Duration (all phases):** 22 weeks

---

## Conclusion

**Key Finding:** CatVRF is already production-ready with Passkeys and modern authentication. The migration plan focuses on:

1. **Phase 1 (Immediate):** Migrate any remaining password users to Passkeys (6 weeks)
2. **Phase 2 (Future):** Migrate to DID/VC for portable identity (12 weeks, after implementation)
3. **Phase 3 (Future):** Migrate services to OAuth2 + mTLS (3 weeks, after implementation)
4. **Phase 4 (Future):** Enable fallback methods for accessibility (1 week, after implementation)

**Recommendation:** Start Phase 1 immediately (Passkey migration) to eliminate password-based authentication. Other phases can be deferred until the corresponding features are implemented.

**Architecture Score Impact:** 8.8/10 → 9.0/10 (after Phase 1) → 9.5/10 (after all phases)

---

**Document Version:** 1.0  
**Last Updated:** April 19, 2026  
**Next Review:** May 19, 2026
