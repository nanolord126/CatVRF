# Technical Specification: Russian Digital Profiles Integration (ESIA/Госуслуги)
**CatVRF Healthcare Marketplace**
**Priority:** HIGH for Russia
**Complexity:** High
**Estimated Effort:** 4-6 weeks
**Date:** April 19, 2026

---

## 1. Overview

### 1.1 Problem Statement

Current CatVRF registration requires users to manually upload documents (passport, INN, etc.) for identity verification. This creates significant friction:

- Poor user experience (manual document upload)
- Higher drop-off rate during registration
- No integration with Russian federal digital identity system
- Competitive disadvantage (Ozon, Wildberries have ESIA)
- Regulatory non-compliance (federal services require ESIA integration)

### 1.2 Solution

Integrate with **ESIA (Единая Система Идентификации и Аутентификации)** - Russian federal digital identity system (Госуслуги).

### 1.3 Key Features

1. **ESIA SSO Login:** Single sign-on via Госуслуги
2. **ESIA User Data Import:** Automatic import of verified user data
3. **Digital Signature Support:** Integration with Russian digital signatures
4. **Federal Services Integration:** Seamless access to federal services
5. **Account Linking:** Link existing CatVRF accounts to ESIA
6. **Biometric Verification:** Use ESIA biometric data
7. **Simplified Business Registration:** ESIA business account integration

### 1.4 Success Criteria

- ESIA login success rate > 95%
- Registration conversion rate increase > 20%
- ESIA user data import accuracy > 99%
- Digital signature verification success rate > 98%
- Zero data breaches (federal compliance)

---

## 2. Architecture

### 2.1 Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                   ESIA Integration Layer                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │  ESIAOAuthClient │───▶│  ESIAAuthFlow    │                   │
│  │  (OAuth 2.0)     │    │  (Orchestrator)  │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ ESIAUserProfile  │───▶│ ESIADataService  │                   │
│  │   Importer       │    │  (API Client)    │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │ DigitalSignature │───▶│ ESIALinkService  │                   │
│  │   Verifier       │    │ (Account Linking)│                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                  │                               │
│                                  ▼                               │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │  ESIAMapper      │───▶│   AuditLogger    │                   │
│  │  (Data Mapping)  │    │  (ClickHouse)    │                   │
│  └──────────────────┘    └──────────────────┘                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Data Flow

```
User clicks "Login with Госуслуги"
            │
            ▼
      ESIAAuthFlow
            │
            ▼
    Redirect to ESIA
            │
            ▼
    User authenticates with ESIA
            │
            ▼
    ESIA redirects back with code
            │
            ▼
      ESIAOAuthClient
    (Exchange code for token)
            │
            ▼
    Fetch user profile from ESIA
            │
            ▼
   ESIAUserProfileImporter
    (Import user data)
            │
            ▼
      ESIAMapper
    (Map to CatVRF schema)
            │
            ▼
    Create/update CatVRF user
            │
            ▼
    Link accounts (if existing)
            │
            ▼
    Create session
            │
            ▼
    Redirect to dashboard
```

### 2.3 Integration Points

- **External:** ESIA API (Госуслуги), GosUslugi SSO
- **Existing:** AuthService, UserService, TenantService, AuditService
- **New:** ESIAAuthService, ESIADataService, ESIALinkService, DigitalSignatureVerifier
- **Middleware:** ESIAAuthMiddleware
- **Storage:** ESIA tokens in Redis, user mappings in MySQL, audit logs in ClickHouse

---

## 3. Detailed Specifications

### 3.1 ESIA OAuth 2.0 Flow

**ESIA Endpoints:**
- Authorization: `https://esia.gosuslugi.ru/aas/oauth2/te`
- Token: `https://esia.gosuslugi.ru/aas/oauth2/te`
- User Info: `https://esia.gosuslugi.ru/rs/prns/{oid}`

**OAuth Flow:**

1. User clicks "Login with Госуслуги"
2. Redirect to ESIA authorization endpoint
3. User authenticates with ESIA (password, biometrics, etc.)
4. ESIA redirects back with authorization code
5. Exchange code for access token
6. Fetch user profile using access token
7. Create/update CatVRF user
8. Create session

### 3.2 ESIAAuthService

**Location:** `app/Services/Auth/ESIAAuthService.php`

**Responsibilities:**
- Orchestrate ESIA OAuth flow
- Manage ESIA tokens
- Handle ESIA authentication
- Link ESIA accounts to CatVRF users

**Key Methods:**

```php
final readonly class ESIAAuthService
{
    public function __construct(
        private readonly ESIAOAuthClient $oauthClient,
        private readonly ESIADataService $dataService,
        private readonly ESIALinkService $linkService,
        private readonly AuthService $authService,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Start ESIA authentication flow
     */
    public function startAuth(string $state = ''): string; // Returns redirect URL

    /**
     * Handle ESIA callback
     */
    public function handleCallback(string $code, string $state): array;

    /**
     * Exchange authorization code for token
     */
    public function exchangeCodeForToken(string $code): array;

    /**
     * Refresh ESIA token
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Validate ESIA token
     */
    public function validateToken(string $accessToken): bool;

    /**
     * Revoke ESIA token
     */
    public function revokeToken(string $accessToken): bool;

    /**
     * Link ESIA account to existing CatVRF user
     */
    public function linkAccount(int $userId, string $esiaOid): bool;

    /**
     * Unlink ESIA account
     */
    public function unlinkAccount(int $userId): bool;
}
```

### 3.3 ESIADataService

**Location:** `app/Services/Auth/ESIADataService.php`

**Responsibilities:**
- Fetch user data from ESIA
- Parse ESIA user profile
- Handle ESIA API errors
- Cache ESIA data

**ESIA User Profile Structure:**

```json
{
  "oid": "1234567890",
  "trusted": true,
  "verified": true,
  "firstName": "Иван",
  "lastName": "Иванов",
  "middleName": "Иванович",
  "birthDate": "1990-01-01",
  "gender": "MALE",
  "snils": "123-456-789 00",
  "inn": "123456789012",
  "documents": [
    {
      "type": "PASSPORT_RF",
      "series": "1234",
      "number": "567890",
      "issueDate": "2010-01-01",
      "issuingAuthority": "УФМС России"
    }
  ],
  "contacts": {
    "email": "ivan@example.com",
    "mobile": "+79001234567"
  },
  "address": {
    "region": "77",
    "city": "Москва",
    "street": "Улица",
    "house": "1"
  }
}
```

**Key Methods:**

```php
final readonly class ESIADataService
{
    public function __construct(
        private readonly ESIAOAuthClient $oauthClient,
    ) {}

    /**
     * Fetch user profile from ESIA
     */
    public function getUserProfile(string $accessToken): array;

    /**
     * Fetch user documents from ESIA
     */
    public function getUserDocuments(string $accessToken, string $esiaOid): array;

    /**
     * Fetch user contacts from ESIA
     */
    public function getUserContacts(string $accessToken, string $esiaOid): array;

    /**
     * Fetch user address from ESIA
     */
    public function getUserAddress(string $accessToken, string $esiaOid): array;

    /**
     * Fetch business profile (for business accounts)
     */
    public function getBusinessProfile(string $accessToken, string $esiaOid): array;

    /**
     * Cache user profile
     */
    public function cacheProfile(string $esiaOid, array $profile): void;

    /**
     * Get cached profile
     */
    public function getCachedProfile(string $esiaOid): ?array;
}
```

### 3.4 ESIAUserProfileImporter

**Location:** `app/Services/Auth/ESIAUserProfileImporter.php`

**Responsibilities:**
- Import ESIA user data to CatVRF
- Map ESIA fields to CatVRF schema
- Validate imported data
- Handle data conflicts

**Mapping:**

| ESIA Field | CatVRF Field | Transformation |
|------------|--------------|----------------|
| oid | esia_oid | Direct |
| firstName | first_name | Direct |
| lastName | last_name | Direct |
| middleName | middle_name | Direct |
| birthDate | date_of_birth | Date format |
| snils | snils | Format validation |
| inn | inn | Format validation |
| documents.type | document_type | Enum mapping |
| documents.series | passport_series | Direct |
| documents.number | passport_number | Direct |
| contacts.email | email | Direct |
| contacts.mobile | phone | Format normalization |
| address.region | region | Code to name mapping |
| address.city | city | Direct |

**Key Methods:**

```php
final readonly class ESIAUserProfileImporter
{
    public function __construct(
        private readonly ESIAMapper $mapper,
        private readonly UserService $userService,
    ) {}

    /**
     * Import ESIA user profile
     */
    public function importProfile(array $esiaProfile): User;

    /**
     * Update existing user with ESIA data
     */
    public function updateUser(User $user, array $esiaProfile): User;

    /**
     * Map ESIA profile to CatVRF user data
     */
    public function mapProfile(array $esiaProfile): array;

    /**
     * Validate imported data
     */
    public function validateData(array $mappedData): bool;

    /**
     * Handle data conflicts
     */
    public function resolveConflicts(User $user, array $mappedData): array;
}
```

### 3.5 ESIALinkService

**Location:** `app/Services/Auth/ESIALinkService.php`

**Responsibilities:**
- Link ESIA accounts to CatVRF users
- Unlink accounts
- Check if account is linked
- Handle multiple ESIA accounts per user

**Key Methods:**

```php
final readonly class ESIALinkService
{
    public function __construct() {}

    /**
     * Link ESIA account to CatVRF user
     */
    public function linkAccount(int $userId, string $esiaOid, array $esiaData): ESIALink;

    /**
     * Unlink ESIA account
     */
    public function unlinkAccount(int $userId, string $esiaOid): bool;

    /**
     * Check if ESIA account is linked
     */
    public function isLinked(string $esiaOid): bool;

    /**
     * Get CatVRF user by ESIA OID
     */
    public function getUserByEsiaOid(string $esiaOid): ?User;

    /**
     * Get all ESIA links for user
     */
    public function getUserLinks(int $userId): array;

    /**
     * Set primary ESIA account
     */
    public function setPrimaryAccount(int $userId, string $esiaOid): bool;
}
```

### 3.6 DigitalSignatureVerifier

**Location:** `app/Services/Security/DigitalSignatureVerifier.php`

**Responsibilities:**
- Verify Russian digital signatures (GOST)
- Integrate with federal digital signature services
- Validate certificate chains
- Check certificate revocation

**Key Methods:**

```php
final readonly class DigitalSignatureVerifier
{
    public function __construct() {}

    /**
     * Verify digital signature
     */
    public function verifySignature(string $data, string $signature, string $certificate): array;

    /**
     * Validate certificate
     */
    public function validateCertificate(string $certificate): array;

    /**
     * Check certificate revocation
     */
    public function checkRevocation(string $certificate): bool;

    /**
     * Extract certificate information
     */
    public function extractCertificateInfo(string $certificate): array;

    /**
     * Sign data with digital signature
     */
    public function signData(string $data, string $privateKey): string;
}
```

### 3.7 ESIAMapper

**Location:** `app/Services/Auth/ESIAMapper.php`

**Responsibilities:**
- Map ESIA data structures to CatVRF structures
- Handle data transformations
- Normalize data formats

**Key Methods:**

```php
final readonly class ESIAMapper
{
    public function __construct() {}

    /**
     * Map ESIA user profile to CatVRF user
     */
    public function mapUser(array $esiaProfile): array;

    /**
     * Map ESIA document to CatVRF document
     */
    public function mapDocument(array $esiaDocument): array;

    /**
     * Map ESIA address to CatVRF address
     */
    public function mapAddress(array $esiaAddress): array;

    /**
     * Map ESIA business profile to CatVRF business
     */
    public function mapBusiness(array $esiaBusiness): array;

    /**
     * Normalize phone number
     */
    public function normalizePhone(string $phone): string;

    /**
     * Normalize INN
     */
    public function normalizeInn(string $inn): string;

    /**
     * Normalize SNILS
     */
    public function normalizeSnils(string $snils): string;
}
```

---

## 4. Database Schema

### 4.1 esia_links Table

**Migration:** `database/migrations/2026_04_19_000007_create_esia_links_table.php`

```php
Schema::create('esia_links', function (Blueprint $table) {
    $table->id();
    
    // CatVRF user
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('set null');
    
    // ESIA data
    $table->string('esia_oid')->unique(); // ESIA user identifier
    $table->string('esia_email')->nullable();
    $table->string('esia_phone')->nullable();
    
    // Link status
    $table->boolean('is_primary')->default(false);
    $table->boolean('is_verified')->default(true); // ESIA accounts are always verified
    $table->timestamp('verified_at')->nullable();
    
    // Token storage (encrypted)
    $table->text('access_token')->nullable();
    $table->text('refresh_token')->nullable();
    $table->timestamp('token_expires_at')->nullable();
    
    // ESIA profile snapshot
    $table->json('esia_profile_snapshot')->nullable();
    
    // Metadata
    $table->ipAddress('linked_from_ip')->nullable();
    $table->string('linked_from_user_agent')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    
    // Timestamps
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index('user_id');
    $table->index('tenant_id');
    $table->index('esia_oid');
    $table->index('is_primary');
});
```

### 4.2 digital_signatures Table

**Migration:** `database/migrations/2026_04_19_000008_create_digital_signatures_table.php`

```php
Schema::create('digital_signatures', function (Blueprint $table) {
    $table->id();
    
    // User
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('set null');
    
    // Certificate data
    $table->string('certificate_fingerprint')->unique();
    $table->text('certificate_data'); // PEM format
    $table->string('certificate_serial')->nullable();
    $table->timestamp('certificate_issued_at')->nullable();
    $table->timestamp('certificate_expires_at')->nullable();
    $table->string('issuer')->nullable();
    
    // Certificate status
    $table->boolean('is_valid')->default(true);
    $table->boolean('is_revoked')->default(false);
    $table->timestamp('revoked_at')->nullable();
    $table->string('revocation_reason')->nullable();
    
    // Usage
    $table->boolean('is_primary')->default(false);
    $table->integer('usage_count')->default(0);
    $table->timestamp('last_used_at')->nullable();
    
    // Metadata
    $table->json('certificate_info')->nullable();
    
    // Timestamps
    $table->timestamps();
    $table->softDeletes();
    
    // Indexes
    $table->index('user_id');
    $table->index('tenant_id');
    $table->index('certificate_fingerprint');
    $table->index('is_valid');
    $table->index('certificate_expires_at');
});
```

---

## 5. Configuration

**File:** `config/esia.php`

```php
return [
    // Enable/disable ESIA integration
    'enabled' => env('ESIA_ENABLED', true),
    
    // ESIA OAuth configuration
    'oauth' => [
        'client_id' => env('ESIA_CLIENT_ID'),
        'client_secret' => env('ESIA_CLIENT_SECRET'),
        'redirect_uri' => env('ESIA_REDIRECT_URI', 'https://catvrf.ru/auth/esia/callback'),
        'scope' => 'openid profile email mobile phone documents',
        'state' => env('ESIA_STATE_SECRET'),
    ],
    
    // ESIA API endpoints
    'api' => [
        'base_url' => env('ESIA_API_URL', 'https://esia.gosuslugi.ru'),
        'auth_url' => env('ESIA_AUTH_URL', 'https://esia.gosuslugi.ru/aas/oauth2/te'),
        'token_url' => env('ESIA_TOKEN_URL', 'https://esia.gosuslugi.ru/aas/oauth2/te'),
        'user_info_url' => env('ESIA_USER_INFO_URL', 'https://esia.gosuslugi.ru/rs/prns'),
    ],
    
    // Token configuration
    'token' => [
        'access_token_ttl' => 3600, // 1 hour
        'refresh_token_ttl' => 2592000, // 30 days
        'auto_refresh' => true,
    ],
    
    // Data import configuration
    'import' => [
        'auto_create_user' => true,
        'auto_link_existing' => true,
        'require_email_verification' => false, // ESIA is trusted
        'require_phone_verification' => false, // ESIA is trusted
        'sync_profile_on_login' => true,
    ],
    
    // Digital signature configuration
    'digital_signature' => [
        'enabled' => env('DIGITAL_SIGNATURE_ENABLED', true),
        'gost_provider' => env('GOST_PROVIDER', 'native'), // native, crypto_pro
        'verify_certificate_chain' => true,
        'check_revocation' => true,
    ],
    
    // Cache configuration
    'cache' => [
        'profile_ttl' => 3600, // 1 hour
        'token_ttl' => 3600, // 1 hour
    ],
    
    // Security
    'security' => [
        'encrypt_tokens' => true,
        'encrypt_certificates' => true,
        'validate_state' => true,
        'require_https' => true,
    ],
];
```

---

## 6. API Endpoints

**File:** `routes/api/esia.php`

```php
Route::prefix('v1/auth/esia')->group(function () {
    // Start ESIA authentication
    Route::get('/login', [ESIAAuthController::class, 'login']);
    
    // Handle ESIA callback
    Route::get('/callback', [ESIAAuthController::class, 'callback']);
    
    // Link ESIA account (for authenticated users)
    Route::middleware('auth:sanctum')->post('/link', [ESIAAuthController::class, 'link']);
    
    // Unlink ESIA account
    Route::middleware('auth:sanctum')->post('/unlink', [ESIAAuthController::class, 'unlink']);
    
    // Get ESIA profile
    Route::middleware('auth:sanctum')->get('/profile', [ESIAAuthController::class, 'getProfile']);
    
    // Sync ESIA profile
    Route::middleware('auth:sanctum')->post('/sync', [ESIAAuthController::class, 'syncProfile']);
    
    // Refresh ESIA token
    Route::middleware('auth:sanctum')->post('/refresh', [ESIAAuthController::class, 'refreshToken']);
});

Route::middleware('auth:sanctum')->prefix('v1/digital-signature')->group(function () {
    // Upload digital signature certificate
    Route::post('/upload', [DigitalSignatureController::class, 'upload']);
    
    // Verify digital signature
    Route::post('/verify', [DigitalSignatureController::class, 'verify']);
    
    // Sign data with digital signature
    Route::post('/sign', [DigitalSignatureController::class, 'sign']);
    
    // List user certificates
    Route::get('/certificates', [DigitalSignatureController::class, 'listCertificates']);
    
    // Delete certificate
    Route::delete('/certificates/{certificate}', [DigitalSignatureController::class, 'deleteCertificate']);
});
```

---

## 7. Frontend Integration

### 7.1 Vue Components

**ESIALoginButton.vue**

```vue
<template>
  <button @click="loginWithESIA" class="esia-login-btn">
    <img src="/images/esia-logo.svg" alt="Госуслуги" />
    Войти через Госуслуги
  </button>
</template>

<script setup>
import { useESIAAuth } from '@/composables/useESIAAuth';

const { loginWithESIA } = useESIAAuth();
</script>
```

**useESIAAuth.ts**

```typescript
import { ref } from 'vue';

export function useESIAAuth() {
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  const loginWithESIA = async () => {
    isLoading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/api/v1/auth/esia/login', {
        method: 'GET',
      });
      
      const data = await response.json();
      
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    } catch (e) {
      error.value = 'Failed to initiate ESIA login';
    } finally {
      isLoading.value = false;
    }
  };

  const linkESIAAccount = async () => {
    // Implementation for linking existing account
  };

  return {
    loginWithESIA,
    linkESIAAccount,
    isLoading,
    error,
  };
}
```

### 7.2 Blade Templates

**auth/esia-login.blade.php**

```blade
<div class="esia-login-container">
    <a href="{{ route('esia.login') }}" class="esia-btn">
        <img src="{{ asset('images/esia-logo.svg') }}" alt="Госуслуги">
        <span>Войти через Госуслуги</span>
    </a>
</div>
```

---

## 8. Testing

### 8.1 Unit Tests

**File:** `tests/Unit/Services/Auth/ESIAAuthServiceTest.php`

```php
class ESIAAuthServiceTest extends TestCase
{
    public function test_start_auth_generates_redirect_url()
    {
        // Test redirect URL generation
    }

    public function test_handle_callback_creates_user()
    {
        // Test user creation from ESIA callback
    }

    public function test_exchange_code_for_token()
    {
        // Test token exchange
    }

    public function test_refresh_token()
    {
        // Test token refresh
    }

    public function test_link_account()
    {
        // Test account linking
    }
}
```

### 8.2 Feature Tests

**File:** `tests/Feature/ESIAAuthenticationTest.php`

```php
class ESIAAuthenticationTest extends TestCase
{
    public function test_esia_login_redirects_to_esia()
    {
        // Test login redirect
    }

    public function test_esia_callback_creates_new_user()
    {
        // Test callback with new user
    }

    public function test_esia_callback_links_existing_user()
    {
        // Test callback with existing user
    }

    public function test_esia_login_creates_session()
    {
        // Test session creation
    }

    public function test_esia_profile_sync()
    {
        // Test profile synchronization
    }
}
```

### 8.3 Integration Tests

**File:** `tests/Integration/ESIAIntegrationTest.php`

```php
class ESIAIntegrationTest extends TestCase
{
    public function test_esia_oauth_flow()
    {
        // Test full OAuth flow with mock ESIA
    }

    public function test_esia_data_import()
    {
        // Test data import from ESIA
    }

    public function test_digital_signature_verification()
    {
        // Test digital signature verification
    }
}
```

---

## 9. Monitoring & Observability

### 9.1 Prometheus Metrics

```php
// Metrics to export
- esia_login_attempts_total
- esia_login_successes_total
- esia_login_failures_total
- esia_account_links_total
- esia_profile_syncs_total
- esia_token_refreshes_total
- esia_api_requests_total
- esia_api_request_duration_seconds
- digital_signature_verifications_total
- digital_signature_verification_failures_total
```

### 9.2 Grafana Dashboard

Create dashboard with panels:
- ESIA login success rate
- ESIA login failure rate
- Account linking rate
- Profile sync success rate
- Token refresh rate
- Digital signature verification rate
- ESIA API response time

### 9.3 Alerts

- Alert if ESIA login failure rate > 10%
- Alert if ESIA API response time > 5s
- Alert if digital signature verification failure rate > 5%
- Alert if ESIA token refresh fails

---

## 10. Security Considerations

### 10.1 OAuth Security

- State parameter validation (CSRF protection)
- PKCE (Proof Key for Code Exchange) for mobile apps
- Token encryption at rest
- Secure token storage in Redis

### 10.2 Data Protection

- Encrypt ESIA tokens and certificates
- PII handling compliance (152-ФЗ)
- Data minimization (only import necessary fields)
- Right-to-be-forgotten support

### 10.3 Fraud Control

- ESIA login passes through FraudControlService
- Rate limiting on ESIA login attempts
- IP-based throttling
- Device fingerprinting

### 10.4 Audit Logging

- All ESIA operations logged to ClickHouse
- ESIA OID correlation
- Token refresh events
- Account linking/unlinking events

### 10.5 Certificate Security

- GOST signature verification
- Certificate chain validation
- Revocation checking
- Certificate expiration monitoring

---

## 11. Rollout Plan

### Phase 1: Registration with ESIA (Week 1)
- Register CatVRF with ESIA (Госуслуги)
- Obtain client credentials
- Configure OAuth endpoints
- Set up test environment

### Phase 2: Development (Week 2-3)
- Implement ESIA OAuth flow
- Implement data import
- Implement account linking
- Implement digital signature support
- Unit and integration tests

### Phase 3: Testing (Week 4)
- Test with ESIA sandbox
- Test with real ESIA accounts (beta users)
- Security testing
- Performance testing
- User acceptance testing

### Phase 4: Gradual Rollout (Week 5)
- Enable for 10% of new users
- Monitor success rate
- Collect user feedback
- Adjust flow based on feedback

### Phase 5: Full Rollout (Week 6)
- Enable for 100% of new users
- Offer ESIA linking to existing users
- Monitor performance
- Documentation and training

---

## 12. Success Metrics

- ESIA login success rate > 95%
- Registration conversion rate increase > 20%
- ESIA user data import accuracy > 99%
- Digital signature verification success rate > 98%
- Average ESIA login time < 5 seconds
- User satisfaction > 4.5/5

---

## 13. Dependencies

**External Services:**
- ESIA API (Госуслуги)
- GosUslugi SSO
- Federal digital signature services

**Existing Services:**
- AuthService
- UserService
- TenantService
- FraudControlService
- AuditService

**Infrastructure:**
- MySQL (user data, ESIA links)
- Redis (token caching)
- ClickHouse (audit logs)
- OpenSSL/GOST (digital signatures)

**Libraries:**
- league/oauth2-client (OAuth 2.0)
- php-gost (GOST signatures, optional)

---

## 14. Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| ESIA API downtime | Medium | High | Fallback to regular login, caching |
| ESIA data structure changes | Low | Medium | Versioned mapper, fallback fields |
| Digital signature compatibility | Medium | Medium | Support multiple providers, fallback |
| User resistance | Low | Medium | Clear UX, benefits explanation |
| Regulatory changes | Medium | High | Monitor regulations, flexible architecture |

---

## 15. Open Questions

1. **ESIA Sandbox:** Do we have access to ESIA sandbox for testing?
2. **Digital Signature Provider:** Should we use CryptoPro or native GOST?
3. **Mobile App:** Should ESIA login be available in mobile app?
4. **Business Accounts:** Should we support ESIA business accounts?
5. **Data Retention:** How long should we store ESIA tokens and certificates?

---

## 16. Appendix: ESIA Registration Process

### Step 1: Register with ESIA
1. Go to https://esia.gosuslugi.ru/
2. Register as information system
3. Submit documentation
4. Wait for approval (2-4 weeks)

### Step 2: Obtain Credentials
1. Receive client_id and client_secret
2. Configure redirect URIs
3. Set up test environment access

### Step 3: Integration Testing
1. Test with ESIA sandbox
2. Verify OAuth flow
3. Test data import
4. Test digital signatures

### Step 4: Production Launch
1. Request production access
2. Update endpoints
3. Perform security audit
4. Launch to users

---

## 17. Compliance Checklist

- [x] 152-ФЗ compliance (personal data)
- [x] Federal Law No. 63-FZ (electronic signatures)
- [x] ESIA integration requirements
- [x] GOST R 34.10-2012 (digital signatures)
- [x] Federal Law No. 152-FZ (data localization)
- [x] Federal Law No. 149-FZ (information protection)

---

**Document Status:** Draft
**Next Review:** April 26, 2026
**Approved By:** [Pending]
