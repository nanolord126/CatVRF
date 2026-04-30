# Technical Specification: Consent Engine & Privacy Enhancements
## Gap #3 - HIGH PRIORITY for GDPR/152-ФЗ Compliance

**Document Version:** 1.0  
**Date:** April 19, 2026  
**Priority:** P1 (HIGH)  
**Estimated Effort:** 2-3 weeks  
**Complexity:** MEDIUM

---

## 1. Executive Summary

This specification details the implementation of privacy engine enhancements to the existing ConsentManagementService. While basic consent management exists (grant/revoke/hasConsent), critical privacy features are missing: data minimization, right-to-be-forgotten workflow, AI provider consent management, and data retention policies.

**Current State:** ConsentManagementService EXISTS (5.0/10) - basic implementation but missing privacy engine components.

---

## 2. Current State Analysis

### Existing Components
- ✅ `ConsentManagementService.php` - Basic grant/revoke/hasConsent operations
- ✅ `ConsentRecord` model - Database schema with consent tracking
- ✅ Granular consent types (biometric, behavioral, location, medical, payment, analytics, marketing, sharing, ai_training)
- ✅ Consent versioning support
- ✅ Consent expiry handling

### Missing Components (To Be Implemented)
- ❌ `DataMinimizationService` - Automatic PII redaction based on consent scope
- ❌ `RightToBeForgottenWorkflow` - 72-hour data deletion cascade
- ❌ `DataRetentionPolicyService` - Automatic data deletion based on retention policies
- ❌ `AIProviderConsentService` - Separate consent for sharing with external AI providers
- ❌ `ConsentVersioningService` - Handle consent updates with user re-confirmation

---

## 3. Service Specifications

### 3.1 DataMinimizationService

**Purpose:** Automatically redact PII based on user's consent scope. Ensure only data user consented to is accessible.

**Methods:**

```php
final readonly class DataMinimizationService
{
    /**
     * Redact PII from data based on user's consents
     * 
     * @param int $userId User ID
     * @param array $data Raw data with PII
     * @param string $context Context (medical, payment, analytics, etc.)
     * @return array Redacted data
     */
    public function redactPII(int $userId, array $data, string $context): array;
    
    /**
     * Check if user has consent for specific data type
     * 
     * @param int $userId User ID
     * @param string $dataType Data type (name, phone, email, etc.)
     * @param string $context Context
     * @return bool Has consent
     */
    public function hasConsentForData(int $userId, string $dataType, string $context): bool;
    
    /**
     * Get redaction rules for context
     * 
     * @param string $context Context
     * @return array Redaction rules
     */
    public function getRedactionRules(string $context): array;
    
    /**
     * Mask sensitive data for logging
     * 
     * @param array $data Raw data
     * @param array $fieldsToMask Fields to mask
     * @return array Masked data
     */
    public function maskForLogging(array $data, array $fieldsToMask): array;
}
```

**Redaction Rules Configuration:**

```php
// config/data-minimization.php
return [
    'contexts' => [
        'medical' => [
            'required_consents' => ['medical', 'biometric'],
            'redaction_rules' => [
                'name' => ['mask' => true, 'method' => 'partial'],
                'phone' => ['mask' => true, 'method' => 'partial'],
                'email' => ['mask' => true, 'method' => 'partial'],
                'address' => ['mask' => true, 'method' => 'partial'],
                'medical_records' => ['mask' => false, 'require_consent' => true],
            ],
        ],
        'payment' => [
            'required_consents' => ['payment'],
            'redaction_rules' => [
                'card_number' => ['mask' => true, 'method' => 'full'],
                'card_expiry' => ['mask' => true, 'method' => 'partial'],
                'card_cvv' => ['mask' => true, 'method' => 'full'],
                'bank_account' => ['mask' => true, 'method' => 'partial'],
            ],
        ],
        'analytics' => [
            'required_consents' => ['analytics'],
            'redaction_rules' => [
                'name' => ['mask' => true, 'method' => 'full'],
                'phone' => ['mask' => true, 'method' => 'full'],
                'email' => ['mask' => true, 'method' => 'hash'],
                'ip_address' => ['mask' => true, 'method' => 'partial'],
            ],
        ],
        'ai_training' => [
            'required_consents' => ['ai_training'],
            'redaction_rules' => [
                'name' => ['mask' => true, 'method' => 'full'],
                'phone' => ['mask' => true, 'method' => 'full'],
                'email' => ['mask' => true, 'method' => 'hash'],
                'address' => ['mask' => true, 'method' => 'full'],
                'behavioral_data' => ['mask' => false, 'require_consent' => true],
            ],
        ],
    ],
    
    'masking_methods' => [
        'partial' => function ($value) {
            // Show first 2 and last 2 characters
            $length = mb_strlen($value);
            if ($length <= 4) return str_repeat('*', $length);
            return mb_substr($value, 0, 2) . str_repeat('*', $length - 4) . mb_substr($value, -2);
        },
        'full' => function ($value) {
            return str_repeat('*', mb_strlen($value));
        },
        'hash' => function ($value) {
            return hash('sha256', $value);
        },
    ],
];
```

**Example Usage:**

```php
// Raw medical data
$medicalData = [
    'name' => 'Иванов Иван Иванович',
    'phone' => '+79001234567',
    'email' => 'ivan@example.com',
    'medical_records' => 'Diagnosis: ...',
];

// Redact based on user's consents
$redactedData = $dataMinimization->redactPII($userId, $medicalData, 'medical');

// Result if user has medical consent
[
    'name' => 'Ив*** Иванович',
    'phone' => '+79******67',
    'email' => 'iv***@example.com',
    'medical_records' => 'Diagnosis: ...',
]

// Result if user does NOT have medical consent
[
    'name' => '*************',
    'phone' => '***********',
    'email' => '*************',
    'medical_records' => '[REDACTED - No consent]',
]
```

---

### 3.2 RightToBeForgottenWorkflow

**Purpose:** Implement GDPR Article 17 right to erasure - 72-hour data deletion cascade across all services.

**Methods:**

```php
final readonly class RightToBeForgottenWorkflow
{
    /**
     * Execute right-to-be-forgotten workflow
     * 
     * @param int $userId User ID
     * @param string $reason Reason for deletion
     * @param string $correlationId Correlation ID
     * @return array Workflow result
     */
    public function execute(
        int $userId,
        string $reason,
        string $correlationId = ''
    ): array;
    
    /**
     * Delete user data from all services
     * 
     * @param int $userId User ID
     * @param string $correlationId Correlation ID
     * @return array Deletion results
     */
    public function deleteUserData(int $userId, string $correlationId = ''): array;
    
    /**
     * Anonymize user data (for legal hold)
     * 
     * @param int $userId User ID
     * @param string $correlationId Correlation ID
     * @return array Anonymization results
     */
    public function anonymizeUserData(int $userId, string $correlationId = ''): array;
    
    /**
     * Verify complete deletion
     * 
     * @param int $userId User ID
     * @return array Verification result
     */
    public function verifyDeletion(int $userId): array;
}
```

**Deletion Steps:**

```php
public function deleteUserData(int $userId, string $correlationId = ''): array
{
    $results = [];
    
    // Step 1: Delete behavioral biometrics data
    $results['behavioral_baselines'] = DB::table('behavioral_baselines')
        ->where('user_id', $userId)
        ->delete();
    
    $results['behavioral_samples'] = DB::table('behavioral_samples')
        ->where('user_id', $userId)
        ->delete();
    
    // Step 2: Delete voice profiles
    $results['voice_profiles'] = DB::table('voice_profiles')
        ->where('user_id', $userId)
        ->delete();
    
    // Step 3: Delete face reference images (from S3)
    $faceReferences = DB::table('face_references')
        ->where('user_id', $userId)
        ->get();
    
    foreach ($faceReferences as $ref) {
        Storage::disk('s3')->delete($ref->image_path);
    }
    
    $results['face_references'] = DB::table('face_references')
        ->where('user_id', $userId)
        ->delete();
    
    // Step 4: Delete consent records
    $results['consent_records'] = DB::table('consent_records')
        ->where('user_id', $userId)
        ->delete();
    
    // Step 5: Delete KYB verification data (for B2B users)
    $results['kyb_verifications'] = DB::table('kyb_verifications')
        ->where('user_id', $userId)
        ->delete();
    
    // Step 6: Anonymize audit logs (keep for compliance, but anonymize PII)
    $results['audit_logs_anonymized'] = $this->anonymizeAuditLogs($userId);
    
    // Step 7: Delete payment transactions (anonymize instead for legal hold)
    $results['payment_transactions_anonymized'] = $this->anonymizePaymentTransactions($userId);
    
    // Step 8: Delete or anonymize medical records
    $results['medical_records'] = $this->deleteMedicalRecords($userId);
    
    // Step 9: Delete user account
    $results['user_deleted'] = User::where('id', $userId)->delete();
    
    // Step 10: Log deletion for audit
    $this->audit->record(
        action: 'right_to_be_forgotten_executed',
        subjectType: User::class,
        subjectId: $userId,
        newValues: [
            'reason' => $reason,
            'deletion_results' => $results,
        ],
        correlationId: $correlationId,
    );
    
    return $results;
}
```

**72-Hour SLA:**

```php
// Schedule deletion job
DeleteUserDataJob::dispatch($userId, $reason, $correlationId)
    ->delay(now()->addHours(72))
    ->onQueue('high-priority');

// Send notification to user
Notification::route('mail', $user->email)
    ->notify(new RightToBeForgottenConfirmation($user));
```

---

### 3.3 DataRetentionPolicyService

**Purpose:** Implement automatic data deletion based on retention policies and consent expiry.

**Methods:**

```php
final readonly class DataRetentionPolicyService
{
    /**
     * Apply retention policies to user data
     * 
     * @param int $userId User ID
     * @return array Deletion results
     */
    public function applyRetentionPolicies(int $userId): array;
    
    /**
     * Check for expired consents and delete associated data
     * 
     * @return int Number of consents processed
     */
    public function processExpiredConsents(): int;
    
    /**
     * Get retention policy for data type
     * 
     * @param string $dataType Data type
     * @return array Retention policy
     */
    public function getRetentionPolicy(string $dataType): array;
    
    /**
     * Schedule periodic cleanup job
     * 
     * @return void
     */
    public function scheduleCleanup(): void;
}
```

**Retention Policies Configuration:**

```php
// config/retention.php
return [
    'policies' => [
        'behavioral_samples' => [
            'retention_days' => 90,
            'on_consent_revoke' => 'delete_immediately',
        ],
        'behavioral_baselines' => [
            'retention_days' => 365,
            'on_consent_revoke' => 'delete_immediately',
        ],
        'voice_profiles' => [
            'retention_days' => 365,
            'on_consent_revoke' => 'delete_immediately',
        ],
        'face_references' => [
            'retention_days' => 365,
            'on_consent_revoke' => 'delete_immediately',
        ],
        'medical_records' => [
            'retention_days' => 2555, // 7 years (medical standard)
            'on_consent_revoke' => 'anonymize',
        ],
        'payment_transactions' => [
            'retention_days' => 1825, // 5 years (financial standard)
            'on_consent_revoke' => 'anonymize',
        ],
        'consent_records' => [
            'retention_days' => 2555, // 7 years for audit
            'on_consent_revoke' => 'keep',
        ],
        'audit_logs' => [
            'retention_days' => 2555, // 7 years for audit
            'on_consent_revoke' => 'anonymize',
        ],
    ],
    
    'cleanup_schedule' => 'daily', // daily, weekly, monthly
];
```

---

### 3.4 AIProviderConsentService

**Purpose:** Manage separate consent for sharing data with external AI providers (OpenAI, Grok, etc.) per AI Act requirements.

**Methods:**

```php
final readonly class AIProviderConsentService
{
    /**
     * Grant consent for specific AI provider
     * 
     * @param int $userId User ID
     * @param string $provider AI provider (openai, grok, anthropic, etc.)
     * @param array $dataTypes Data types allowed to share
     * @param string $correlationId Correlation ID
     * @return array Consent result
     */
    public function grantProviderConsent(
        int $userId,
        string $provider,
        array $dataTypes,
        string $correlationId = ''
    ): array;
    
    /**
     * Revoke consent for specific AI provider
     * 
     * @param int $userId User ID
     * @param string $provider AI provider
     * @param string $correlationId Correlation ID
     * @return array Revocation result
     */
    public function revokeProviderConsent(
        int $userId,
        string $provider,
        string $correlationId = ''
    ): array;
    
    /**
     * Check if user has consent for AI provider
     * 
     * @param int $userId User ID
     * @param string $provider AI provider
     * @param string $dataType Data type
     * @return bool Has consent
     */
    public function hasProviderConsent(
        int $userId,
        string $provider,
        string $dataType
    ): bool;
    
    /**
     * Anonymize data before sending to AI provider
     * 
     * @param int $userId User ID
     * @param string $provider AI provider
     * @param array $data Raw data
     * @return array Anonymized data
     */
    public function anonymizeForProvider(
        int $userId,
        string $provider,
        array $data
    ): array;
}
```

**Database Schema:**

```sql
CREATE TABLE ai_provider_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL, -- openai, grok, anthropic, etc.
    data_types JSON NOT NULL, -- ['medical_records', 'behavioral_data']
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    revocation_reason TEXT,
    correlation_id VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_provider (user_id, provider),
    INDEX idx_provider (provider)
);
```

---

### 3.5 ConsentVersioningService

**Purpose:** Handle consent version updates with user re-confirmation when consent terms change.

**Methods:**

```php
final readonly class ConsentVersioningService
{
    /**
     * Create new consent version
     * 
     * @param string $consentType Consent type
     * @param string $version Version number
     * @param array $changes Changes from previous version
     * @return array Version result
     */
    public function createVersion(
        string $consentType,
        string $version,
        array $changes
    ): array;
    
    /**
     * Check if user needs to re-confirm consent
     * 
     * @param int $userId User ID
     * @param string $consentType Consent type
     * @return bool Needs re-confirmation
     */
    public function needsReconfirmation(int $userId, string $consentType): bool;
    
    /**
     * Get pending re-confirmations for user
     * 
     * @param int $userId User ID
     * @return array Pending re-confirmations
     */
    public function getPendingReconfirmations(int $userId): array;
    
    /**
     * Re-confirm consent with new version
     * 
     * @param int $userId User ID
     * @param string $consentType Consent type
     * @param string $correlationId Correlation ID
     * @return array Re-confirmation result
     */
    public function reconfirm(
        int $userId,
        string $consentType,
        string $correlationId = ''
    ): array;
}
```

**Database Schema:**

```sql
CREATE TABLE consent_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consent_type VARCHAR(50) NOT NULL,
    version VARCHAR(20) NOT NULL,
    changes JSON NOT NULL,
    effective_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deprecated_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type_version (consent_type, version)
);

CREATE TABLE consent_reconfirmations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    consent_type VARCHAR(50) NOT NULL,
    from_version VARCHAR(20) NOT NULL,
    to_version VARCHAR(20) NOT NULL,
    status ENUM('pending', 'confirmed', 'declined') DEFAULT 'pending',
    confirmed_at TIMESTAMP NULL,
    declined_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
);
```

---

## 4. Database Schema Updates

### Updates to Existing Tables

```sql
-- Update consent_records table with new fields
ALTER TABLE consent_records
ADD COLUMN version VARCHAR(20) AFTER consent_type,
ADD COLUMN data_retention_days INT UNSIGNED NULL AFTER expires_at,
ADD COLUMN legal_hold BOOLEAN DEFAULT FALSE AFTER revoked_at,
ADD COLUMN deletion_scheduled_at TIMESTAMP NULL AFTER legal_hold;

-- Add index for expired consent cleanup
CREATE INDEX idx_expired_consents ON consent_records(granted, expires_at)
WHERE expires_at IS NOT NULL;
```

### New Tables

```sql
-- Data deletion requests (for right-to-be-forgotten)
CREATE TABLE data_deletion_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    deletion_results JSON,
    correlation_id VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Data retention audit log
CREATE TABLE data_retention_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL, -- deleted, anonymized, retained
    reason VARCHAR(100),
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    performed_by VARCHAR(50), -- system, user_id
    correlation_id VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_action_performed (action, performed_at)
);
```

---

## 5. Configuration

### Environment Variables

```env
# Data Retention
DATA_RETENTION_CLEANUP_SCHEDULE=daily
DATA_RETENTION_LEGAL_HOLD_EMAIL=legal@example.com

# Right to Be Forgotten
RIGHT_TO_BE_FORGOTTEN_SLA_HOURS=72
RIGHT_TO_BE_FORGOTTEN_NOTIFICATION_ENABLED=true

# AI Providers
OPENAI_CONSENT_REQUIRED=true
GROK_CONSENT_REQUIRED=true
ANTHROPIC_CONSENT_REQUIRED=true

# Data Minimization
DATA_MINIMIZATION_ENABLED=true
DATA_MINIMIZATION_LOG_MASKING=true
```

### Config File

```php
// config/privacy.php
return [
    'data_minimization' => [
        'enabled' => env('DATA_MINIMIZATION_ENABLED', true),
        'log_masking' => env('DATA_MINIMIZATION_LOG_MASKING', true),
        'default_context' => 'analytics',
    ],
    
    'right_to_be_forgotten' => [
        'sla_hours' => env('RIGHT_TO_BE_FORGOTTEN_SLA_HOURS', 72),
        'notification_enabled' => env('RIGHT_TO_BE_FORGOTTEN_NOTIFICATION_ENABLED', true),
        'legal_hold_days' => 2555, // 7 years for audit
    ],
    
    'data_retention' => [
        'cleanup_schedule' => env('DATA_RETENTION_CLEANUP_SCHEDULE', 'daily'),
        'legal_hold_email' => env('DATA_RETENTION_LEGAL_HOLD_EMAIL'),
    ],
    
    'ai_providers' => [
        'openai' => [
            'consent_required' => env('OPENAI_CONSENT_REQUIRED', true),
            'allowed_data_types' => ['behavioral_data', 'anonymized_medical'],
        ],
        'grok' => [
            'consent_required' => env('GROK_CONSENT_REQUIRED', true),
            'allowed_data_types' => ['behavioral_data'],
        ],
        'anthropic' => [
            'consent_required' => env('ANTHROPIC_CONSENT_REQUIRED', true),
            'allowed_data_types' => ['behavioral_data'],
        ],
    ],
];
```

---

## 6. Implementation Plan

### Week 1: DataMinimizationService + RightToBeForgottenWorkflow
- Implement DataMinimizationService with redaction rules
- Implement RightToBeForgottenWorkflow with 72-hour SLA
- Create data deletion job (queued)
- Create legal hold support
- Write unit tests
- Update ConsentManagementService to use new services

### Week 2: DataRetentionPolicyService + AIProviderConsentService
- Implement DataRetentionPolicyService with retention policies
- Implement scheduled cleanup job
- Implement AIProviderConsentService
- Create database schema for AI provider consents
- Write unit tests
- Integrate with existing consent management

### Week 3: ConsentVersioningService + Testing
- Implement ConsentVersioningService
- Create consent version management UI (Filament)
- Implement re-confirmation workflow
- Write integration tests
- End-to-end testing
- Documentation

---

## 7. Testing Strategy

### Unit Tests
- `DataMinimizationServiceTest` - Test redaction rules, consent checks
- `RightToBeForgottenWorkflowTest` - Test deletion cascade, verification
- `DataRetentionPolicyServiceTest` - Test retention policies, cleanup
- `AIProviderConsentServiceTest` - Test provider consent, anonymization
- `ConsentVersioningServiceTest` - Test versioning, re-confirmation

### Integration Tests
- `PrivacyEngineIntegrationTest` - Test full privacy workflow
- Test right-to-be-forgotten with real data deletion
- Test data minimization with real PII

### Compliance Tests
- Test GDPR Article 17 compliance
- Test 152-ФЗ Article 10 compliance
- Test AI Act consent requirements

---

## 8. Security Considerations

- **Legal Hold:** Implement legal hold flag to prevent deletion during investigations
- **Audit Trail:** Log all data deletions for compliance
- **Verification:** Verify complete deletion after right-to-be-forgotten
- **Consent Validation:** Ensure consent is valid before allowing data access
- **AI Provider Security:** Anonymize data before sending to external AI providers
- **Encryption:** Encrypt sensitive data at rest and in transit

---

## 9. Performance Considerations

- **Async Deletion:** Use queues for right-to-be-forgotten (72-hour SLA allows async)
- **Batch Cleanup:** Process expired consents in batches
- **Caching:** Cache consent checks to reduce database queries
- **Soft Delete:** Use soft delete for audit trail, hard delete after retention period

---

## 10. Success Criteria

- [ ] DataMinimizationService implemented and tested
- [ ] RightToBeForgottenWorkflow implemented with 72-hour SLA
- [ ] DataRetentionPolicyService implemented with scheduled cleanup
- [ ] AIProviderConsentService implemented
- [ ] ConsentVersioningService implemented
- [ ] Unit test coverage > 80%
- [ ] Integration tests passing
- [ ] GDPR Article 17 compliance verified
- [ ] 152-ФZ Article 10 compliance verified
- [ ] AI Act consent requirements verified
- [ ] Performance: Data minimization < 50ms per request
- [ ] Performance: Right-to-be-forgotten completes within 72 hours

---

## 11. Rollback Plan

If critical issues arise:
1. Disable privacy engine features via feature flags
2. Revert to basic consent management
3. Stop scheduled cleanup jobs
4. Monitor logs
5. Hotfix within 4 hours

---

**Document Status:** Ready for Implementation  
**Next Steps:** Begin Week 1 implementation (DataMinimizationService + RightToBeForgottenWorkflow)
