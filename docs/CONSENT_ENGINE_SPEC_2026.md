# Consent Engine Technical Specification
## Privacy Engine with 152-ФЗ / GDPR Compliance

**Version:** 1.0  
**Date:** April 19, 2026  
**Priority:** 🔴 CRITICAL (Compliance blocker)  
**Estimated Effort:** 3-4 weeks  
**Complexity:** High

---

## 1. Overview

This specification details the implementation of a comprehensive Consent Management and Privacy Engine required for 152-ФЗ (Russia) and GDPR (EU) compliance. The system must provide granular consent tracking, data minimization, right-to-be-forgotten workflows, and automated data deletion.

### Current State
- ✅ Basic audit logging
- ✅ PII masking in logs (basic)
- ❌ No granular consent tracking
- ❌ No data minimization enforcement
- ❌ No right-to-be-forgotten workflow
- ❌ No consent withdrawal automation

### Target State
- Granular consent types (biometric, behavioral, sharing, marketing)
- Version tracking for consent changes
- Purpose-based consent (medical, payments, analytics)
- Expiration and renewal workflows
- Consent withdrawal with automated data cleanup
- Data minimization enforcement
- Right-to-be-forgotten workflow (72-hour SLA)
- Consent dashboard for management

---

## 2. Requirements

### 2.1. Functional Requirements

**Consent Management:**
1. Granular consent types (biometric, behavioral, sharing, marketing, analytics)
2. Purpose-based consent (medical diagnosis, payments, recommendations, analytics)
3. Version tracking for all consent changes
4. Explicit consent (opt-in) required for sensitive data
5. Implicit consent (opt-out) for non-sensitive data
6. Consent expiration with renewal workflows
7. Withdrawal with cascading data deletion
8. Consent history audit trail

**Privacy Engine:**
1. Data minimization enforcement (auto-redaction)
2. PII masking in logs (enhanced)
3. Data retention policies per data type
4. Automated data deletion on consent withdrawal
5. Data portability (GDPR Article 20)
6. Data access logging per consent purpose
7. Purpose-based data access control

**Right-to-be-Forgotten:**
1. 72-hour data deletion SLA
2. Cascading deletion across all services
3. Audit trail for deletion compliance
4. Legal hold exceptions
5. Verification of complete deletion
6. Backup cleanup

### 2.2. Non-Functional Requirements

- Consent lookup latency: <100ms
- Data deletion throughput: 1000 records/second
- API availability: 99.9%
- Audit trail: Immutable (append-only)
- Data encryption: At rest and in transit

---

## 3. Data Model

### 3.1. Consent Types

```php
// app/Enums/ConsentType.php

enum ConsentType: string
{
    case BIOMETRIC = 'biometric';           // Face ID, voice, fingerprints
    case BEHAVIORAL = 'behavioral';       // Typing, mouse, touch patterns
    case LOCATION = 'location';            // GPS, IP-based location
    case MEDICAL = 'medical';              // Health data, symptoms, diagnoses
    case PAYMENT = 'payment';              // Payment methods, transactions
    case ANALYTICS = 'analytics';          // Usage analytics, metrics
    case MARKETING = 'marketing';          // Email, SMS, push notifications
    case SHARING = 'sharing';              // Sharing with third parties
    case AI_TRAINING = 'ai_training';      // Using data for AI model training
}

enum ConsentPurpose: string
{
    case MEDICAL_DIAGNOSIS = 'medical_diagnosis';
    case PAYMENT_PROCESSING = 'payment_processing';
    case RECOMMENDATIONS = 'recommendations';
    case ANALYTICS = 'analytics';
    case MARKETING = 'marketing';
    case IMPROVEMENT = 'improvement';
}

enum ConsentStatus: string
{
    case PENDING = 'pending';
    case GRANTED = 'granted';
    case DENIED = 'denied';
    case WITHDRAWN = 'withdrawn';
    case EXPIRED = 'expired';
}
```

---

## 4. Service Architecture

### 4.1. Consent Management Service

```php
// app/Services/Privacy/ConsentManagementService.php

final readonly class ConsentManagementService
{
    private const CONSENT_EXPIRY_DAYS = 365; // 1 year default
    private const BIOMETRIC_CONSENT_EXPIRY_DAYS = 730; // 2 years for biometric

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DataDeletionService $dataDeletion,
    ) {}

    /**
     * Grant consent for specific type and purpose
     */
    public function grantConsent(
        int $userId,
        string $tenantId,
        ConsentType $consentType,
        ConsentPurpose $purpose,
        array $metadata = [],
        string $correlationId = ''
    ): ConsentRecord {
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'consent_grant',
            amount: 0,
            correlationId: $correlationId,
        );

        // Check if consent already exists and is active
        $existingConsent = ConsentRecord::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('consent_type', $consentType->value)
            ->where('purpose', $purpose->value)
            ->where('status', ConsentStatus::GRANTED->value)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingConsent) {
            return $existingConsent;
        }

        // Calculate expiry date
        $expiryDays = match ($consentType) {
            ConsentType::BIOMETRIC => self::BIOMETRIC_CONSENT_EXPIRY_DAYS,
            default => self::CONSENT_EXPIRY_DAYS,
        };

        // Create consent record
        $consent = ConsentRecord::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'consent_type' => $consentType->value,
            'purpose' => $purpose->value,
            'status' => ConsentStatus::GRANTED->value,
            'granted_at' => now(),
            'granted_ip' => request()->ip(),
            'granted_user_agent' => request()->userAgent(),
            'expires_at' => now()->addDays($expiryDays),
            'metadata' => $metadata,
            'version' => $this->getNextVersion($userId, $tenantId, $consentType, $purpose),
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->logEvent('consent_granted', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'consent_type' => $consentType->value,
            'purpose' => $purpose->value,
            'version' => $consent->version,
        ], 'privacy');

        return $consent;
    }

    /**
     * Withdraw consent with cascading data deletion
     */
    public function withdrawConsent(
        int $userId,
        string $tenantId,
        ConsentType $consentType,
        ?ConsentPurpose $purpose = null, // Null = withdraw all purposes
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: $userId,
            operationType: 'consent_withdrawal',
            amount: 0,
            correlationId: $correlationId,
        );

        // Find active consents
        $query = ConsentRecord::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('consent_type', $consentType->value)
            ->where('status', ConsentStatus::GRANTED->value)
            ->where('expires_at', '>', now());

        if ($purpose !== null) {
            $query->where('purpose', $purpose->value);
        }

        $consents = $query->get();

        if ($consents->isEmpty()) {
            return [
                'success' => false,
                'reason' => 'No active consent found',
            ];
        }

        // Withdraw each consent
        foreach ($consents as $consent) {
            $consent->update([
                'status' => ConsentStatus::WITHDRAWN->value,
                'withdrawn_at' => now(),
                'withdrawn_ip' => request()->ip(),
                'withdrawn_user_agent' => request()->userAgent(),
            ]);

            // Trigger data deletion
            $this->dataDeletion->deleteDataByConsent($consent, $correlationId);

            // Audit log
            $this->audit->logEvent('consent_withdrawn', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'consent_type' => $consentType->value,
                'purpose' => $consent->purpose,
                'version' => $consent->version,
            ], 'privacy');
        }

        return [
            'success' => true,
            'consents_withdrawn' => $consents->count(),
        ];
    }

    /**
     * Check if consent is granted
     */
    public function hasConsent(
        int $userId,
        string $tenantId,
        ConsentType $consentType,
        ConsentPurpose $purpose
    ): bool {
        return ConsentRecord::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('consent_type', $consentType->value)
            ->where('purpose', $purpose->value)
            ->where('status', ConsentStatus::GRANTED->value)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get all user consents
     */
    public function getUserConsents(int $userId, string $tenantId): array
    {
        $consents = ConsentRecord::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('consent_type');

        $result = [];
        foreach ($consents as $type => $typeConsents) {
            $result[$type] = $typeConsents->map(fn($c) => [
                'purpose' => $c->purpose,
                'status' => $c->status,
                'granted_at' => $c->granted_at,
                'expires_at' => $c->expires_at,
                'version' => $c->version,
            ])->toArray();
        }

        return $result;
    }

    /**
     * Renew expired consent
     */
    public function renewConsent(
        int $consentId,
        string $correlationId = ''
    ): ConsentRecord {
        $consent = ConsentRecord::findOrFail($consentId);

        $expiryDays = match ($consent->consent_type) {
            ConsentType::BIOMETRIC->value => self::BIOMETRIC_CONSENT_EXPIRY_DAYS,
            default => self::CONSENT_EXPIRY_DAYS,
        };

        $consent->update([
            'status' => ConsentStatus::GRANTED->value,
            'granted_at' => now(),
            'expires_at' => now()->addDays($expiryDays),
            'version' => $consent->version + 1,
        ]);

        // Audit log
        $this->audit->logEvent('consent_renewed', [
            'consent_id' => $consentId,
            'user_id' => $consent->user_id,
            'tenant_id' => $consent->tenant_id,
            'new_version' => $consent->version,
        ], 'privacy');

        return $consent->fresh();
    }

    /**
     * Get next version number for consent
     */
    private function getNextVersion(
        int $userId,
        string $tenantId,
        ConsentType $consentType,
        ConsentPurpose $purpose
    ): int {
        $lastVersion = ConsentRecord::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('consent_type', $consentType->value)
            ->where('purpose', $purpose->value)
            ->max('version');

        return ($lastVersion ?? 0) + 1;
    }
}
```

### 4.2. Privacy Engine Service

```php
// app/Services/Privacy/PrivacyEngineService.php

final readonly class PrivacyEngineService
{
    public function __construct(
        private readonly ConsentManagementService $consentManagement,
        private readonly PIIMaskingService $piiMasking,
        private readonly DataRetentionService $dataRetention,
        private readonly AuditService $audit,
    ) {}

    /**
     * Check if data access is allowed based on consent
     */
    public function checkDataAccess(
        int $userId,
        string $tenantId,
        ConsentType $consentType,
        ConsentPurpose $purpose,
        string $correlationId = ''
    ): bool {
        $hasConsent = $this->consentManagement->hasConsent(
            $userId,
            $tenantId,
            $consentType,
            $purpose
        );

        if (!$hasConsent) {
            $this->audit->logEvent('data_access_denied_no_consent', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'consent_type' => $consentType->value,
                'purpose' => $purpose->value,
            ], 'privacy');

            return false;
        }

        return true;
    }

    /**
     * Mask PII in data based on consent
     */
    public function maskPII(
        array $data,
        int $userId,
        string $tenantId,
        string $correlationId = ''
    ): array {
        return $this->piiMasking->maskData($data, $userId, $tenantId, $correlationId);
    }

    /**
     * Enforce data retention policies
     */
    public function enforceRetentionPolicies(string $correlationId = ''): array
    {
        return $this->dataRetention->enforcePolicies($correlationId);
    }

    /**
     * Export user data (GDPR Article 20 - Data Portability)
     */
    public function exportUserData(
        int $userId,
        string $tenantId,
        string $correlationId = ''
    ): array {
        $this->audit->logEvent('data_export_requested', [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ], 'privacy');

        // Collect all user data
        $userData = [
            'user' => User::where('id', $userId)->first(),
            'consents' => ConsentRecord::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->get(),
            'medical_records' => MedicalRecord::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->get(),
            'payments' => PaymentRecord::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->get(),
            // Add other data types as needed
        ];

        // Apply PII masking based on consent
        foreach ($userData as $key => $data) {
            if (is_array($data)) {
                $userData[$key] = $this->piiMasking->maskCollection($data, $userId, $tenantId);
            }
        }

        return $userData;
    }
}
```

### 4.3. PII Masking Service

```php
// app/Services/Privacy/PIIMaskingService.php

final readonly class PIIMaskingService
{
    private const MASK_CHAR = '*';
    private const EMAIL_MASK = '***@***.***';
    private const PHONE_MASK = '+7 (***) ***-**-**';
    private const INN_MASK = '**********';

    public function __construct(
        private readonly ConsentManagementService $consentManagement,
    ) {}

    /**
     * Mask PII in data array
     */
    public function maskData(
        array $data,
        int $userId,
        string $tenantId,
        string $correlationId = ''
    ): array {
        $masked = $data;

        foreach ($data as $key => $value) {
            $masked[$key] = $this->maskField($key, $value, $userId, $tenantId);
        }

        return $masked;
    }

    /**
     * Mask collection of records
     */
    public function maskCollection(
        array $collection,
        int $userId,
        string $tenantId
    ): array {
        return array_map(
            fn($item) => $this->maskData($item->toArray(), $userId, $tenantId),
            $collection
        );
    }

    /**
     * Mask specific field based on type and consent
     */
    private function maskField(
        string $field,
        mixed $value,
        int $userId,
        string $tenantId
    ): mixed {
        if ($value === null) {
            return null;
        }

        // Check consent for sensitive fields
        if ($this->isSensitiveField($field)) {
            $hasConsent = $this->consentManagement->hasConsent(
                $userId,
                $tenantId,
                $this->getConsentTypeForField($field),
                ConsentPurpose::ANALYTICS
            );

            if (!$hasConsent) {
                return $this->maskValue($field, $value);
            }
        }

        return $value;
    }

    /**
     * Mask value based on field type
     */
    private function maskValue(string $field, mixed $value): mixed
    {
        if (is_string($value)) {
            return match (true) {
                str_contains($field, 'email') => $this->maskEmail($value),
                str_contains($field, 'phone') || str_contains($field, 'mobile') => $this->maskPhone($value),
                str_contains($field, 'inn') => self::INN_MASK,
                str_contains($field, 'name') => $this->maskName($value),
                str_contains($field, 'address') => $this->maskAddress($value),
                default => $this->maskGeneric($value),
            };
        }

        return null;
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return self::EMAIL_MASK;
        }

        $local = $parts[0];
        $domain = $parts[1];

        $maskedLocal = substr($local, 0, 2) . str_repeat(self::MASK_CHAR, strlen($local) - 2);
        $maskedDomain = str_repeat(self::MASK_CHAR, strlen($domain) - 4) . substr($domain, -4);

        return $maskedLocal . '@' . $maskedDomain;
    }

    private function maskPhone(string $phone): string
    {
        // Remove non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($cleaned) < 10) {
            return self::PHONE_MASK;
        }

        return '+7 ' . str_repeat(self::MASK_CHAR, 3) . ' ' .
               str_repeat(self::MASK_CHAR, 3) . '-' .
               str_repeat(self::MASK_CHAR, 2) . '-' .
               substr($cleaned, -2);
    }

    private function maskName(string $name): string
    {
        $words = explode(' ', $name);
        $maskedWords = [];

        foreach ($words as $word) {
            if (strlen($word) <= 2) {
                $maskedWords[] = str_repeat(self::MASK_CHAR, strlen($word));
            } else {
                $maskedWords[] = substr($word, 0, 1) . str_repeat(self::MASK_CHAR, strlen($word) - 1);
            }
        }

        return implode(' ', $maskedWords);
    }

    private function maskAddress(string $address): string
    {
        // Keep street name, mask house number
        if (preg_match('/(.+?)\s+(\d+.*)/', $address, $matches)) {
            return $matches[1] . ' ' . str_repeat(self::MASK_CHAR, strlen($matches[2]));
        }

        return str_repeat(self::MASK_CHAR, min(strlen($address), 20));
    }

    private function maskGeneric(string $value): string
    {
        $length = min(strlen($value), 10);
        return substr($value, 0, 1) . str_repeat(self::MASK_CHAR, $length - 1);
    }

    private function isSensitiveField(string $field): bool
    {
        $sensitiveFields = [
            'email', 'phone', 'mobile', 'inn', 'name', 'first_name', 'last_name',
            'address', 'passport', 'snils', 'birth_date', 'gender',
        ];

        foreach ($sensitiveFields as $sensitive) {
            if (str_contains($field, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function getConsentTypeForField(string $field): ConsentType
    {
        if (str_contains($field, 'biometric') || str_contains($field, 'face') || str_contains($field, 'voice')) {
            return ConsentType::BIOMETRIC;
        }

        if (str_contains($field, 'location') || str_contains($field, 'gps')) {
            return ConsentType::LOCATION;
        }

        if (str_contains($field, 'medical') || str_contains($field, 'health')) {
            return ConsentType::MEDICAL;
        }

        return ConsentType::ANALYTICS;
    }
}
```

### 4.4. Data Deletion Service

```php
// app/Services/Privacy/DataDeletionService.php

final readonly class DataDeletionService
{
    private const DELETION_BATCH_SIZE = 1000;
    private const MAX_DELETION_TIME_SECONDS = 72 * 3600; // 72 hours

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Delete data by consent withdrawal
     */
    public function deleteDataByConsent(
        ConsentRecord $consent,
        string $correlationId = ''
    ): array {
        $startTime = now();
        $deletedRecords = [];

        try {
            // Dispatch deletion job to queue
            $job = new DeleteDataByConsentJob($consent->id, $correlationId);
            dispatch($job);

            return [
                'success' => true,
                'job_id' => $job->getJobId(),
                'status' => 'queued',
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to queue data deletion', [
                'consent_id' => $consent->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reason' => 'Failed to queue deletion job',
            ];
        }
    }

    /**
     * Execute right-to-be-forgotten request
     */
    public function executeRightToBeForgotten(
        int $userId,
        string $tenantId,
        ?array $exceptions = null, // Legal hold exceptions
        string $correlationId = ''
    ): array {
        $startTime = now();
        $deletedCounts = [];

        try {
            DB::beginTransaction();

            // Delete user data from all tables
            $deletedCounts['users'] = User::where('id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            $deletedCounts['consents'] = ConsentRecord::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            $deletedCounts['medical_records'] = MedicalRecord::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            $deletedCounts['payments'] = PaymentRecord::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            $deletedCounts['behavioral_profiles'] = BehavioralProfile::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            // Delete voice profiles
            $deletedCounts['voice_profiles'] = VoiceProfile::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->delete();

            // Delete webauthn credentials
            $deletedCounts['webauthn_credentials'] = WebauthnCredential::where('user_id', $userId)
                ->delete();

            // Delete audit logs (unless legal hold)
            if (!($exceptions['keep_audit_logs'] ?? false)) {
                $deletedCounts['audit_logs'] = DB::table('audit_logs')
                    ->where('user_id', $userId)
                    ->where('tenant_id', $tenantId)
                    ->delete();
            }

            DB::commit();

            $duration = now()->diffInSeconds($startTime);

            // Audit log
            $this->audit->logEvent('right_to_be_forgotten_executed', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'deleted_counts' => $deletedCounts,
                'duration_seconds' => $duration,
            ], 'privacy');

            return [
                'success' => true,
                'deleted_counts' => $deletedCounts,
                'duration_seconds' => $duration,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Right-to-be-forgotten execution failed', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify complete deletion
     */
    public function verifyDeletion(
        int $userId,
        string $tenantId,
        string $correlationId = ''
    ): array {
        $remainingRecords = [];

        // Check all tables for remaining records
        $tables = [
            'users',
            'consent_records',
            'medical_records',
            'payment_records',
            'behavioral_profiles',
            'voice_profiles',
            'webauthn_credentials',
        ];

        foreach ($tables as $table) {
            $count = DB::table($table)
                ->where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->count();

            if ($count > 0) {
                $remainingRecords[$table] = $count;
            }
        }

        $isComplete = empty($remainingRecords);

        return [
            'is_complete' => $isComplete,
            'remaining_records' => $remainingRecords,
        ];
    }
}
```

### 4.5. Data Retention Service

```php
// app/Services/Privacy/DataRetentionService.php

final readonly class DataRetentionService
{
    // Retention policies (in days)
    private const RETENTION_POLICIES = [
        'audit_logs' => 2555, // 7 years
        'consent_records' => 2555, // 7 years
        'medical_records' => 3650, // 10 years (152-ФZ requirement)
        'payment_records' => 2555, // 7 years
        'behavioral_profiles' => 365, // 1 year
        'voice_profiles' => 365, // 1 year
        'webauthn_credentials' => 365, // 1 year
    ];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Enforce retention policies
     */
    public function enforcePolicies(string $correlationId = ''): array
    {
        $deletedCounts = [];

        foreach (self::RETENTION_POLICIES as $table => $retentionDays) {
            $cutoffDate = now()->subDays($retentionDays);

            $deleted = DB::table($table)
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $deletedCounts[$table] = $deleted;
        }

        // Audit log
        $this->audit->logEvent('retention_policies_enforced', [
            'deleted_counts' => $deletedCounts,
        ], 'privacy');

        return [
            'success' => true,
            'deleted_counts' => $deletedCounts,
        ];
    }
}
```

---

## 5. Database Schema

```php
// database/migrations/2026_04_19_000008_create_consent_records_table.php

public function up(): void
{
    Schema::create('consent_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        $table->enum('consent_type', [
            'biometric', 'behavioral', 'location', 'medical',
            'payment', 'analytics', 'marketing', 'sharing', 'ai_training'
        ]);
        $table->enum('purpose', [
            'medical_diagnosis', 'payment_processing', 'recommendations',
            'analytics', 'marketing', 'improvement'
        ]);
        $table->enum('status', ['pending', 'granted', 'denied', 'withdrawn', 'expired']);
        $table->timestamp('granted_at')->nullable();
        $table->string('granted_ip')->nullable();
        $table->text('granted_user_agent')->nullable();
        $table->timestamp('withdrawn_at')->nullable();
        $table->string('withdrawn_ip')->nullable();
        $table->text('withdrawn_user_agent')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->integer('version')->default(1);
        $table->json('metadata')->nullable();
        $table->string('correlation_id')->nullable();
        $table->timestamps();
        
        $table->index(['user_id', 'tenant_id', 'consent_type', 'purpose']);
        $table->index(['user_id', 'status', 'expires_at']);
        $table->index('expires_at');
    });
}

// database/migrations/2026_04_19_000009_create_data_deletion_requests_table.php

public function up(): void
{
    Schema::create('data_deletion_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        $table->foreignId('consent_record_id')->nullable()->constrained('consent_records')->onDelete('set null');
        $table->enum('request_type', ['consent_withdrawal', 'right_to_be_forgotten']);
        $table->enum('status', ['pending', 'in_progress', 'completed', 'failed']);
        $table->json('deleted_counts')->nullable();
        $table->integer('total_records_deleted')->default(0);
        $table->timestamp('started_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->integer('duration_seconds')->nullable();
        $table->text('failure_reason')->nullable();
        $table->json('exceptions')->nullable(); // Legal hold exceptions
        $table->string('correlation_id')->nullable();
        $table->timestamps();
        
        $table->index(['user_id', 'status']);
        $table->index('status');
        $table->index('created_at');
    });
}
```

---

## 6. API Endpoints

```php
// app/Http/Controllers/Api/ConsentController.php

final class ConsentController extends Controller
{
    public function __construct(
        private readonly ConsentManagementService $consentManagement,
        private readonly PrivacyEngineService $privacyEngine,
        private readonly DataDeletionService $dataDeletion,
    ) {}

    /**
     * Grant consent
     * POST /api/v1/privacy/consent/grant
     */
    public function grantConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|in:biometric,behavioral,location,medical,payment,analytics,marketing,sharing,ai_training',
            'purpose' => 'required|string|in:medical_diagnosis,payment_processing,recommendations,analytics,marketing,improvement',
            'metadata' => 'nullable|array',
        ]);

        $consent = $this->consentManagement->grantConsent(
            $request->user()->id,
            $request->user()->tenant_id,
            ConsentType::from($validated['consent_type']),
            ConsentPurpose::from($validated['purpose']),
            $validated['metadata'] ?? [],
            $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'consent' => $consent->toArray(),
        ]);
    }

    /**
     * Withdraw consent
     * POST /api/v1/privacy/consent/withdraw
     */
    public function withdrawConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string',
            'purpose' => 'nullable|string',
        ]);

        $result = $this->consentManagement->withdrawConsent(
            $request->user()->id,
            $request->user()->tenant_id,
            ConsentType::from($validated['consent_type']),
            $validated['purpose'] ? ConsentPurpose::from($validated['purpose']) : null,
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }

    /**
     * Get user consents
     * GET /api/v1/privacy/consent
     */
    public function getConsents(Request $request): JsonResponse
    {
        $consents = $this->consentManagement->getUserConsents(
            $request->user()->id,
            $request->user()->tenant_id,
        );

        return response()->json([
            'consents' => $consents,
        ]);
    }

    /**
     * Check consent
     * GET /api/v1/privacy/consent/check
     */
    public function checkConsent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string',
            'purpose' => 'required|string',
        ]);

        $hasConsent = $this->consentManagement->hasConsent(
            $request->user()->id,
            $request->user()->tenant_id,
            ConsentType::from($validated['consent_type']),
            ConsentPurpose::from($validated['purpose']),
        );

        return response()->json([
            'has_consent' => $hasConsent,
        ]);
    }

    /**
     * Export user data (GDPR Article 20)
     * GET /api/v1/privacy/data/export
     */
    public function exportData(Request $request): JsonResponse
    {
        $data = $this->privacyEngine->exportUserData(
            $request->user()->id,
            $request->user()->tenant_id,
            $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Request right-to-be-forgotten
     * POST /api/v1/privacy/data/delete
     */
    public function requestDeletion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exceptions' => 'nullable|array',
        ]);

        $result = $this->dataDeletion->executeRightToBeForgotten(
            $request->user()->id,
            $request->user()->tenant_id,
            $validated['exceptions'] ?? null,
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }

    /**
     * Verify deletion completion
     * GET /api/v1/privacy/data/verify-deletion
     */
    public function verifyDeletion(Request $request): JsonResponse
    {
        $result = $this->dataDeletion->verifyDeletion(
            $request->user()->id,
            $request->user()->tenant_id,
            $request->header('X-Correlation-ID'),
        );

        return response()->json($result);
    }
}
```

---

## 7. Middleware

```php
// app/Http/Middleware/RequireConsent.php

final class RequireConsent
{
    public function __construct(
        private readonly PrivacyEngineService $privacyEngine,
    ) {}

    public function handle(Request $request, Closure $next, string $consentType, string $purpose): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $hasAccess = $this->privacyEngine->checkDataAccess(
            $user->id,
            $user->tenant_id,
            ConsentType::from($consentType),
            ConsentPurpose::from($purpose),
            $request->header('X-Correlation-ID'),
        );

        if (!$hasAccess) {
            return response()->json([
                'error' => 'Consent required',
                'consent_type' => $consentType,
                'purpose' => $purpose,
            ], 403);
        }

        return $next($request);
    }
}
```

---

## 8. Scheduled Jobs

```php
// app/Jobs/DeleteDataByConsentJob.php

final class DeleteDataByConsentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $consentId,
        public readonly string $correlationId = ''
    ) {
        $this->onQueue('privacy-deletion');
    }

    public function handle(DataDeletionService $dataDeletion): void
    {
        $consent = ConsentRecord::findOrFail($this->consentId);

        // Create deletion request
        $request = DataDeletionRequest::create([
            'user_id' => $consent->user_id,
            'tenant_id' => $consent->tenant_id,
            'consent_record_id' => $consent->id,
            'request_type' => 'consent_withdrawal',
            'status' => 'in_progress',
            'started_at' => now(),
            'correlation_id' => $this->correlationId,
        ]);

        // Execute deletion based on consent type
        $deletedCounts = match ($consent->consent_type) {
            ConsentType::BIOMETRIC->value => $this->deleteBiometricData($consent),
            ConsentType::BEHAVIORAL->value => $this->deleteBehavioralData($consent),
            ConsentType::LOCATION->value => $this->deleteLocationData($consent),
            ConsentType::MEDICAL->value => $this->deleteMedicalData($consent),
            default => [],
        };

        $request->update([
            'status' => 'completed',
            'deleted_counts' => $deletedCounts,
            'total_records_deleted' => array_sum($deletedCounts),
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($request->started_at),
        ]);
    }

    private function deleteBiometricData(ConsentRecord $consent): array
    {
        $counts = [];

        // Delete voice profiles
        $counts['voice_profiles'] = VoiceProfile::where('user_id', $consent->user_id)
            ->where('tenant_id', $consent->tenant_id)
            ->delete();

        // Delete webauthn credentials (biometric)
        $counts['webauthn_credentials'] = WebauthnCredential::where('user_id', $consent->user_id)
            ->delete();

        return $counts;
    }

    private function deleteBehavioralData(ConsentRecord $consent): array
    {
        $counts = [];

        // Delete behavioral profiles
        $counts['behavioral_profiles'] = BehavioralProfile::where('user_id', $consent->user_id)
            ->where('tenant_id', $consent->tenant_id)
            ->delete();

        return $counts;
    }

    // ... other deletion methods
}

// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // Enforce retention policies - daily at 03:00 UTC
    $schedule->call(fn () => app(DataRetentionService::class)->enforcePolicies())
        ->dailyAt('03:00')
        ->timezone('UTC');

    // Check for expired consents - daily at 04:00 UTC
    $schedule->call(fn () => $this->expireConsents())
        ->dailyAt('04:00')
        ->timezone('UTC');
}
```

---

## 9. Configuration

```php
// config/privacy.php

return [
    'consent' => [
        'default_expiry_days' => 365,
        'biometric_expiry_days' => 730,
        'version_tracking' => true,
    ],
    'retention' => [
        'audit_logs' => 2555, // 7 years
        'consent_records' => 2555, // 7 years
        'medical_records' => 3650, // 10 years (152-ФZ)
        'payment_records' => 2555, // 7 years
        'behavioral_profiles' => 365, // 1 year
        'voice_profiles' => 365, // 1 year
    ],
    'deletion' => [
        'batch_size' => 1000,
        'max_time_seconds' => 259200, // 72 hours
        'queue' => 'privacy-deletion',
    ],
    'masking' => [
        'mask_char' => '*',
        'email_mask' => '***@***.***',
        'phone_mask' => '+7 (***) ***-**-**',
        'inn_mask' => '**********',
    ],
    'gdpr' => [
        'data_portability_enabled' => true,
        'right_to_be_forgotten_enabled' => true,
        'deletion_sla_hours' => 72,
    ],
];
```

---

## 10. Implementation Timeline

**Week 1: Consent Management Service**
- Day 1-2: ConsentManagementService implementation
- Day 3-4: Database migrations + enums
- Day 5: Unit tests

**Week 2: Privacy Engine + PII Masking**
- Day 1-2: PrivacyEngineService implementation
- Day 3-4: PIIMaskingService implementation
- Day 5: Integration + unit tests

**Week 3: Data Deletion + Retention**
- Day 1-3: DataDeletionService implementation
- Day 4: DataRetentionService implementation
- Day 5: Scheduled jobs + queue configuration

**Week 4: Integration + Dashboard**
- Day 1-2: API endpoints + controllers
- Day 3: Middleware implementation
- Day 4: Filament dashboard for consent management
- Day 5: End-to-end testing + documentation

---

## 11. Success Criteria

- [ ] Consent lookup latency <100ms
- [ ] Data deletion throughput 1000 records/second
- [ ] 72-hour deletion SLA met
- [ ] PII masking accuracy 100%
- [ ] Consent version tracking operational
- [ ] GDPR Article 20 (data portability) implemented
- [ ] Right-to-be-forgotten workflow operational
- [ ] All unit tests passing (>90% coverage)
- [ ] 152-ФZ compliance verified
- [ ] GDPR compliance verified

---

**Document Version:** 1.0  
**Last Updated:** April 19, 2026  
**Next Review:** April 26, 2026
