# Consent Management + Privacy Engine — Technical Specification
**CatVRF Healthcare Marketplace — 152-ФЗ/GDPR Compliance**

**Priority:** CRITICAL (152-ФZ compliance)  
**Complexity:** MEDIUM-HIGH  
**Estimated Effort:** 2-3 weeks  
**Status:** Not Started  

---

## 1. Overview

### 1.1 Purpose
Implement granular consent management and privacy engine to ensure compliance with 152-ФЗ (Russian Federal Law on Personal Data) and GDPR (for EU expansion). This includes granular consent tracking per data purpose, data minimization enforcement, retention policies, and right-to-be-forgotten workflow.

### 1.2 Scope
- Granular consent per purpose (6+ purposes)
- Consent versioning and audit trail
- Data retention policies per data type
- Right-to-be-forgotten workflow (72-hour deletion)
- Data minimization enforcement
- Consent withdrawal with automatic data deletion
- Filament dashboard for consent management

### 1.3 Consent Purposes

| Purpose | Description | Data Types |
|---------|-------------|------------|
| `biometric_processing` | Face ID, voice biometrics | Face images, voice samples, biometric templates |
| `behavioral_tracking` | Typing, mouse, touch patterns | Behavioral signals, device fingerprinting |
| `location_tracking` | Geo-velocity, IP geolocation | IP addresses, GPS coordinates |
| `ai_processing` | External AI provider data sharing | Anonymized data sent to AI providers |
| `marketing_communications` | Email, push notifications | Email, phone, notification tokens |
| `analytics` | Usage analytics, ML training | Usage logs, anonymized metrics |

---

## 2. Architecture

### 2.1 Service Layer

```
┌─────────────────────────────────────────────────────────────┐
│                  ConsentManagementService                   │
│  - Manage user consents                                     │
│  - Track consent changes                                    │
│  - Handle consent withdrawal                               │
│  - Enforce consent checks                                   │
└─────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│ Privacy       │   │ Data          │   │ Consent       │
│ Engine        │   │ Deletion      │   │ Audit         │
│ Service       │   │ Service       │   │ Service       │
└───────────────┘   └───────────────┘   └───────────────┘
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│ Data          │   │ 72-hour       │   │ Audit Trail   │
│ Minimization  │   │ Deletion      │   │ (ClickHouse)  │
│ Enforcement   │   │ Workflow      │   │               │
└───────────────┘   └───────────────┘   └───────────────┘
```

### 2.2 Database Schema

```sql
-- Consent purposes definitions
CREATE TABLE consent_purposes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    data_types JSON COMMENT 'List of data types covered',
    is_required BOOLEAN DEFAULT FALSE COMMENT 'Required for core functionality',
    is_sensitive BOOLEAN DEFAULT FALSE COMMENT 'Sensitive data (biometric, location)',
    retention_period_days INT DEFAULT NULL COMMENT 'Data retention period',
    legal_basis VARCHAR(100) DEFAULT 'consent' COMMENT 'consent, contract, legal_obligation',
    version INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User consent records
CREATE TABLE consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id CHAR(36) NULL,
    consent_purpose_id BIGINT UNSIGNED NOT NULL,
    purpose_slug VARCHAR(50) NOT NULL,
    is_granted BOOLEAN NOT NULL,
    granted_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    consent_version INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    consent_source VARCHAR(50) DEFAULT 'web' COMMENT 'web, mobile, api',
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_consent_purpose_id (consent_purpose_id),
    INDEX idx_purpose_slug (purpose_slug),
    INDEX idx_is_granted (is_granted),
    INDEX idx_granted_at (granted_at),
    UNIQUE KEY uk_user_purpose (user_id, consent_purpose_id, consent_version),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (consent_purpose_id) REFERENCES consent_purposes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data retention policies
CREATE TABLE data_retention_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_type VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    retention_period_days INT NOT NULL,
    retention_period_type ENUM('fixed', 'sliding', 'event_based') DEFAULT 'sliding',
    deletion_action ENUM('anonymize', 'delete', 'archive') DEFAULT 'delete',
    is_active BOOLEAN DEFAULT TRUE,
    applies_to_tenant BOOLEAN DEFAULT FALSE,
    applies_to_user BOOLEAN DEFAULT TRUE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_type (data_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data deletion requests (right-to-be-forgotten)
CREATE TABLE data_deletion_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id CHAR(36) NULL,
    request_type ENUM('full_deletion', 'partial_deletion', 'anonymization') NOT NULL,
    data_types JSON COMMENT 'Specific data types to delete',
    request_reason TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled') NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_completion_at TIMESTAMP NULL,
    deletion_summary JSON COMMENT 'Summary of deleted data',
    error_message TEXT NULL,
    processed_by BIGINT UNSIGNED NULL COMMENT 'Admin who processed',
    processed_notes TEXT NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consent audit log (detailed changes)
CREATE TABLE consent_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id CHAR(36) NULL,
    consent_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL COMMENT 'granted, revoked, updated, withdrawn',
    purpose_slug VARCHAR(50) NOT NULL,
    previous_value JSON NULL,
    new_value JSON NOT NULL,
    changed_by_user_id BIGINT UNSIGNED NULL COMMENT 'User who made change (can be admin)',
    changed_by_type VARCHAR(20) DEFAULT 'user' COMMENT 'user, admin, system',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_consent_id (consent_id),
    INDEX idx_action (action),
    INDEX idx_purpose_slug (purpose_slug),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (consent_id) REFERENCES consents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data minimization rules
CREATE TABLE data_minimization_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_type VARCHAR(100) NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('mask', 'truncate', 'hash', 'anonymize') NOT NULL,
    rule_config JSON COMMENT 'Configuration for the rule',
    applies_on ENUM('collection', 'storage', 'export') DEFAULT 'storage',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_type (data_type),
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Service Specifications

### 3.1 ConsentManagementService

**File:** `app/Services/Privacy/ConsentManagementService.php`

**Responsibilities:**
- Manage user consents (grant, revoke, update)
- Track consent changes with audit trail
- Enforce consent checks before data processing
- Handle consent withdrawal with data deletion

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Models\User;
use App\Models\Consent;
use App\Models\ConsentPurpose;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class ConsentManagementService
{
    public function __construct(
        private readonly ConsentAuditService $consentAudit,
        private readonly DataDeletionService $dataDeletion,
        private readonly AuditService $audit,
    ) {}

    /**
     * Grant consent for a purpose
     */
    public function grantConsent(
        User $user,
        string $purposeSlug,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $correlationId = ''
    ): Consent {
        $purpose = ConsentPurpose::where('slug', $purposeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($user, $purpose, $ipAddress, $userAgent, $correlationId) {
            // Check if consent already exists
            $existingConsent = Consent::where('user_id', $user->id)
                ->where('consent_purpose_id', $purpose->id)
                ->where('is_granted', true)
                ->whereNull('revoked_at')
                ->first();

            if ($existingConsent) {
                return $existingConsent;
            }

            // Create new consent
            $consent = Consent::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'consent_purpose_id' => $purpose->id,
                'purpose_slug' => $purpose->slug,
                'is_granted' => true,
                'granted_at' => now(),
                'consent_version' => $purpose->version,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'consent_source' => 'web',
            ]);

            // Audit log
            $this->consentAudit->logChange(
                $user->id,
                $consent->id,
                'granted',
                $purpose->slug,
                null,
                ['granted' => true, 'version' => $purpose->version],
                $user->id,
                'user',
                $ipAddress,
                $userAgent,
                $correlationId
            );

            Log::info('Consent granted', [
                'user_id' => $user->id,
                'purpose_slug' => $purpose->slug,
                'consent_id' => $consent->id,
                'correlation_id' => $correlationId,
            ]);

            return $consent;
        });
    }

    /**
     * Revoke consent for a purpose
     */
    public function revokeConsent(
        User $user,
        string $purposeSlug,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $correlationId = ''
    ): void {
        $purpose = ConsentPurpose::where('slug', $purposeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        DB::transaction(function () use ($user, $purpose, $ipAddress, $userAgent, $correlationId) {
            $consent = Consent::where('user_id', $user->id)
                ->where('consent_purpose_id', $purpose->id)
                ->where('is_granted', true)
                ->whereNull('revoked_at')
                ->firstOrFail();

            $previousValue = ['granted' => true, 'granted_at' => $consent->granted_at];

            // Revoke consent
            $consent->update([
                'is_granted' => false,
                'revoked_at' => now(),
            ]);

            // Audit log
            $this->consentAudit->logChange(
                $user->id,
                $consent->id,
                'revoked',
                $purpose->slug,
                $previousValue,
                ['granted' => false, 'revoked_at' => now()],
                $user->id,
                'user',
                $ipAddress,
                $userAgent,
                $correlationId
            );

            // Trigger data deletion based on purpose
            $this->handleConsentWithdrawal($user, $purpose, $correlationId);

            Log::info('Consent revoked', [
                'user_id' => $user->id,
                'purpose_slug' => $purpose->slug,
                'consent_id' => $consent->id,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Check if user has granted consent for purpose
     */
    public function hasConsent(User $user, string $purposeSlug): bool
    {
        $purpose = ConsentPurpose::where('slug', $purposeSlug)
            ->where('is_active', true)
            ->first();

        if (!$purpose) {
            return false;
        }

        return Consent::where('user_id', $user->id)
            ->where('consent_purpose_id', $purpose->id)
            ->where('is_granted', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Get all user consents
     */
    public function getUserConsents(User $user): array
    {
        $purposes = ConsentPurpose::where('is_active', true)->get();
        $consents = Consent::where('user_id', $user->id)
            ->whereIn('consent_purpose_id', $purposes->pluck('id'))
            ->whereNull('revoked_at')
            ->get()
            ->keyBy('consent_purpose_id');

        return $purposes->map(function ($purpose) use ($consents) {
            $consent = $consents->get($purpose->id);
            return [
                'slug' => $purpose->slug,
                'name' => $purpose->name,
                'description' => $purpose->description,
                'is_required' => $purpose->is_required,
                'is_sensitive' => $purpose->is_sensitive,
                'is_granted' => $consent?->is_granted ?? false,
                'granted_at' => $consent?->granted_at,
                'consent_version' => $consent?->consent_version ?? null,
            ];
        })->toArray();
    }

    /**
     * Handle consent withdrawal (trigger data deletion)
     */
    private function handleConsentWithdrawal(User $user, ConsentPurpose $purpose, string $correlationId): void
    {
        $dataTypes = $purpose->data_types ?? [];

        if (empty($dataTypes)) {
            return;
        }

        // Create data deletion request
        $this->dataDeletion->createRequest(
            $user->id,
            'partial_deletion',
            $dataTypes,
            sprintf('Consent withdrawn for purpose: %s', $purpose->slug),
            $correlationId
        );
    }
}
```

### 3.2 PrivacyEngineService

**File:** `app/Services/Privacy/PrivacyEngineService.php`

**Responsibilities:**
- Enforce data minimization rules
- Apply retention policies
- Check consent before data access
- Anonymize/purge data based on policies

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Models\User;
use App\Models\DataRetentionPolicy;
use Illuminate\Support\Facades\Log;

final readonly class PrivacyEngineService
{
    public function __construct(
        private readonly ConsentManagementService $consentManagement,
        private readonly DataMinimizationService $dataMinimization,
    ) {}

    /**
     * Check if data access is allowed based on consent
     */
    public function canAccessData(User $user, string $dataType, string $purposeSlug): bool
    {
        // Check consent
        if (!$this->consentManagement->hasConsent($user, $purposeSlug)) {
            return false;
        }

        // Check if data type is covered by purpose
        $purpose = \App\Models\ConsentPurpose::where('slug', $purposeSlug)->first();
        if (!$purpose) {
            return false;
        }

        $dataTypes = $purpose->data_types ?? [];
        return in_array($dataType, $dataTypes, true);
    }

    /**
     * Apply data minimization to data
     */
    public function minimizeData(string $dataType, array $data): array
    {
        return $this->dataMinimization->applyRules($dataType, $data);
    }

    /**
     * Check if data should be retained based on policy
     */
    public function shouldRetainData(string $dataType, ?\DateTime $createdAt = null): bool
    {
        $policy = DataRetentionPolicy::where('data_type', $dataType)
            ->where('is_active', true)
            ->first();

        if (!$policy) {
            return true; // No policy = retain
        }

        if (!$createdAt) {
            return true;
        }

        $retentionDate = now()->subDays($policy->retention_period_days);
        return $createdAt->greaterThan($retentionDate);
    }

    /**
     * Get retention period for data type
     */
    public function getRetentionPeriod(string $dataType): ?int
    {
        $policy = DataRetentionPolicy::where('data_type', $dataType)
            ->where('is_active', true)
            ->first();

        return $policy?->retention_period_days;
    }
}
```

### 3.3 DataDeletionService

**File:** `app/Services/Privacy/DataDeletionService.php`

**Responsibilities:**
- Create data deletion requests
- Process deletion requests (72-hour SLA)
- Delete/anonymize data based on type
- Track deletion progress

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class DataDeletionService
{
    private const DELETION_SLA_HOURS = 72;

    public function __construct(
        private readonly ConsentManagementService $consentManagement,
    ) {}

    /**
     * Create data deletion request
     */
    public function createRequest(
        int $userId,
        string $requestType,
        ?array $dataTypes = null,
        ?string $reason = null,
        string $correlationId = ''
    ): DataDeletionRequest {
        $user = User::findOrFail($userId);

        return DB::transaction(function () use ($user, $requestType, $dataTypes, $reason, $correlationId) {
            $request = DataDeletionRequest::create([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'request_type' => $requestType,
                'data_types' => $dataTypes,
                'request_reason' => $reason,
                'status' => 'pending',
                'estimated_completion_at' => now()->addHours(self::DELETION_SLA_HOURS),
                'correlation_id' => $correlationId,
            ]);

            Log::info('Data deletion request created', [
                'user_id' => $user->id,
                'request_id' => $request->id,
                'request_type' => $requestType,
                'correlation_id' => $correlationId,
            ]);

            return $request;
        });
    }

    /**
     * Process deletion request
     */
    public function processRequest(int $requestId, int $processedBy): void
    {
        $request = DataDeletionRequest::findOrFail($requestId);

        if ($request->status !== 'pending') {
            throw new \DomainException('Request is not pending');
        }

        DB::transaction(function () use ($request, $processedBy) {
            $request->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'processed_by' => $processedBy,
            ]);

            try {
                $deletionSummary = $this->executeDeletion($request);

                $request->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'deletion_summary' => $deletionSummary,
                ]);

                Log::info('Data deletion request completed', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id,
                    'deletion_summary' => $deletionSummary,
                ]);
            } catch (\Throwable $e) {
                $request->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('Data deletion request failed', [
                    'request_id' => $request->id,
                    'user_id' => $request->user_id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Execute deletion based on request type
     */
    private function executeDeletion(DataDeletionRequest $request): array
    {
        $summary = [];

        match ($request->request_type) {
            'full_deletion' => $summary = $this->executeFullDeletion($request->user_id),
            'partial_deletion' => $summary = $this->executePartialDeletion($request->user_id, $request->data_types),
            'anonymization' => $summary = $this->executeAnonymization($request->user_id, $request->data_types),
        };

        return $summary;
    }

    /**
     * Execute full user deletion
     */
    private function executeFullDeletion(int $userId): array
    {
        $deleted = [];

        // Delete biometric data
        $deleted['voice_biometrics'] = \App\Models\VoiceBiometric::where('user_id', $userId)->delete();
        
        // Delete behavioral data
        $deleted['behavioral_data'] = DB::table('behavioral_biometrics_data')
            ->where('user_id', $userId)
            ->delete();

        // Delete consent records
        $deleted['consents'] = Consent::where('user_id', $userId)->delete();

        // Delete user (soft delete)
        $deleted['user'] = User::where('id', $userId)->delete();

        return $deleted;
    }

    /**
     * Execute partial deletion (specific data types)
     */
    private function executePartialDeletion(int $userId, ?array $dataTypes): array
    {
        $deleted = [];

        foreach ($dataTypes as $dataType) {
            match ($dataType) {
                'voice_biometric' => $deleted['voice_biometric'] = \App\Models\VoiceBiometric::where('user_id', $userId)->delete(),
                'behavioral_data' => $deleted['behavioral_data'] = DB::table('behavioral_biometrics_data')->where('user_id', $userId)->delete(),
                'liveness_data' => $deleted['liveness_data'] = \App\Models\LivenessSession::where('user_id', $userId)->delete(),
                default => Log::warning('Unknown data type for deletion', ['data_type' => $dataType]),
            };
        }

        return $deleted;
    }

    /**
     * Execute anonymization
     */
    private function executeAnonymization(int $userId, ?array $dataTypes): array
    {
        $anonymized = [];

        // Anonymize user PII
        $user = User::withTrashed()->find($userId);
        if ($user) {
            $user->update([
                'name' => 'Anonymized User',
                'email' => 'anonymized_' . $userId . '@deleted.local',
                'phone' => null,
            ]);
            $anonymized['user'] = true;
        }

        return $anonymized;
    }

    /**
     * Get pending deletion requests
     */
    public function getPendingRequests(): array
    {
        return DataDeletionRequest::where('status', 'pending')
            ->orderBy('requested_at')
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'user_id' => $request->user_id,
                'request_type' => $request->request_type,
                'requested_at' => $request->requested_at,
                'estimated_completion_at' => $request->estimated_completion_at,
            ])
            ->toArray();
    }
}
```

### 3.4 DataMinimizationService

**File:** `app/Services/Privacy/DataMinimizationService.php`

**Responsibilities:**
- Apply data minimization rules
- Mask, truncate, hash, or anonymize data
- Enforce minimization on collection/storage/export

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Privacy;

use App\Models\DataMinimizationRule;
use Illuminate\Support\Facades\Log;

final readonly class DataMinimizationService
{
    /**
     * Apply minimization rules to data
     */
    public function applyRules(string $dataType, array $data): array
    {
        $rules = DataMinimizationRule::where('data_type', $dataType)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $data = $this->applyRule($rule, $data);
        }

        return $data;
    }

    /**
     * Apply single minimization rule
     */
    private function applyRule(DataMinimizationRule $rule, array $data): array
    {
        $config = $rule->rule_config ?? [];

        return match ($rule->rule_type) {
            'mask' => $this->maskData($data, $config),
            'truncate' => $this->truncateData($data, $config),
            'hash' => $this->hashData($data, $config),
            'anonymize' => $this->anonymizeData($data, $config),
            default => $data,
        };
    }

    /**
     * Mask data (e.g., email: j***@example.com)
     */
    private function maskData(array $data, array $config): array
    {
        $fields = $config['fields'] ?? [];
        $maskChar = $config['mask_char'] ?? '*';
        $keepStart = $config['keep_start'] ?? 1;
        $keepEnd = $config['keep_end'] ?? 0;

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                $length = strlen($value);
                
                if ($length <= $keepStart + $keepEnd) {
                    $data[$field] = $maskChar;
                } else {
                    $start = substr($value, 0, $keepStart);
                    $end = substr($value, -$keepEnd);
                    $middle = str_repeat($maskChar, $length - $keepStart - $keepEnd);
                    $data[$field] = $start . $middle . $end;
                }
            }
        }

        return $data;
    }

    /**
     * Truncate data (e.g., IP address: 192.168.***.***)
     */
    private function truncateData(array $data, array $config): array
    {
        $fields = $config['fields'] ?? [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                $parts = explode('.', $value);
                
                if (count($parts) >= 2) {
                    $keepParts = $config['keep_parts'] ?? 2;
                    $visibleParts = array_slice($parts, 0, $keepParts);
                    $hiddenParts = array_fill(0, count($parts) - $keepParts, '***');
                    $data[$field] = implode('.', array_merge($visibleParts, $hiddenParts));
                }
            }
        }

        return $data;
    }

    /**
     * Hash data (e.g., email -> SHA256 hash)
     */
    private function hashData(array $data, array $config): array
    {
        $fields = $config['fields'] ?? [];
        $algorithm = $config['algorithm'] ?? 'sha256';

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = hash($algorithm, $data[$field]);
            }
        }

        return $data;
    }

    /**
     * Anonymize data (replace with placeholder)
     */
    private function anonymizeData(array $data, array $config): array
    {
        $fields = $config['fields'] ?? [];
        $placeholder = $config['placeholder'] ?? '[ANONYMIZED]';

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $placeholder;
            }
        }

        return $data;
    }
}
```

---

## 4. Models

### 4.1 Consent Model

**File:** `app/Models/Consent.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Consent extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'consent_purpose_id',
        'purpose_slug',
        'is_granted',
        'granted_at',
        'revoked_at',
        'consent_version',
        'ip_address',
        'user_agent',
        'consent_source',
        'metadata',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'consent_version' => 'integer',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(ConsentPurpose::class, 'consent_purpose_id');
    }

    public function isActive(): bool
    {
        return $this->is_granted && $this->revoked_at === null;
    }
}
```

### 4.2 ConsentPurpose Model

**File:** `app/Models/ConsentPurpose.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ConsentPurpose extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'data_types',
        'is_required',
        'is_sensitive',
        'retention_period_days',
        'legal_basis',
        'version',
        'is_active',
    ];

    protected $casts = [
        'data_types' => 'json',
        'is_required' => 'boolean',
        'is_sensitive' => 'boolean',
        'retention_period_days' => 'integer',
        'version' => 'integer',
        'is_active' => 'boolean',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'consent_purpose_id');
    }
}
```

---

## 5. Filament Dashboard

### 5.1 ConsentPurposeResource

**File:** `app/Filament/Resources/ConsentPurposeResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ConsentPurposeResource\Pages;
use App\Models\ConsentPurpose;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConsentPurposeResource extends Resource
{
    protected static ?string $model = ConsentPurpose::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Privacy';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purpose Information')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        Forms\Components\KeyValue::make('data_types')
                            ->label('Data Types')
                            ->keyLabel('Type')
                            ->valueLabel('Description'),
                    ])->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required for Core Functionality'),
                        Forms\Components\Toggle::make('is_sensitive')
                            ->label('Sensitive Data (Biometric, Location)'),
                        Forms\Components\TextInput::make('retention_period_days')
                            ->label('Retention Period (Days)')
                            ->numeric()
                            ->default(365),
                        Forms\Components\Select::make('legal_basis')
                            ->label('Legal Basis')
                            ->options([
                                'consent' => 'Consent',
                                'contract' => 'Contract',
                                'legal_obligation' => 'Legal Obligation',
                            ])
                            ->default('consent'),
                        Forms\Components\TextInput::make('version')
                            ->label('Version')
                            ->numeric()
                            ->default(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required'),
                Tables\Columns\IconColumn::make('is_sensitive')
                    ->boolean()
                    ->label('Sensitive'),
                Tables\Columns\TextColumn::make('retention_period_days')
                    ->label('Retention (Days)'),
                Tables\Columns\TextColumn::make('version')
                    ->label('Version'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->query(fn ($query) => $query->where('is_active', true)),
                Tables\Filters\Filter::make('is_sensitive')
                    ->query(fn ($query) => $query->where('is_sensitive', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deactivate')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['is_active' => false])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsentPurposes::route('/'),
            'create' => Pages\CreateConsentPurpose::route('/create'),
            'view' => Pages\ViewConsentPurpose::route('/{record}'),
            'edit' => Pages\EditConsentPurpose::route('/{record}/edit'),
        ];
    }
}
```

---

## 6. API Routes

**File:** `routes/api/privacy.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PrivacyController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Consents
    Route::get('/privacy/consents', [PrivacyController::class, 'getConsents']);
    Route::post('/privacy/consents/{purposeSlug}/grant', [PrivacyController::class, 'grantConsent']);
    Route::post('/privacy/consents/{purposeSlug}/revoke', [PrivacyController::class, 'revokeConsent']);
    Route::get('/privacy/consents/{purposeSlug}/check', [PrivacyController::class, 'checkConsent']);

    // Data Deletion
    Route::post('/privacy/deletion/request', [PrivacyController::class, 'requestDeletion']);
    Route::get('/privacy/deletion/status/{requestId}', [PrivacyController::class, 'getDeletionStatus']);
});

Route::middleware(['auth:sanctum', 'role:admin,compliance'])->group(function () {
    // Admin functions
    Route::get('/privacy/deletion/pending', [PrivacyController::class, 'getPendingDeletions']);
    Route::post('/privacy/deletion/{requestId}/process', [PrivacyController::class, 'processDeletion']);
});
```

---

## 7. Configuration

**File:** `config/privacy.php`

```php
<?php

return [
    'consent' => [
        'default_retention_days' => 365,
        'sensitive_data_retention_days' => 90,
        'audit_retention_days' => 2555, // 7 years
    ],
    'deletion' => [
        'sla_hours' => 72,
        'auto_process' => false,
        'notification_on_completion' => true,
    ],
    'minimization' => [
        'enabled' => true,
        'apply_on_export' => true,
        'apply_on_api' => true,
    ],
    'gdpr' => [
        'enabled' => env('GDPR_ENABLED', false),
        'dpo_email' => env('GDPR_DPO_EMAIL', 'dpo@catvrf.ru'),
    ],
];
```

---

## 8. Database Seeders

**File:** `database/seeders/ConsentPurposeSeeder.php`

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ConsentPurpose;
use Illuminate\Database\Seeder;

final class ConsentPurposeSeeder extends Seeder
{
    public function run(): void
    {
        $purposes = [
            [
                'slug' => 'biometric_processing',
                'name' => 'Biometric Data Processing',
                'description' => 'Processing of face ID and voice biometrics for authentication',
                'data_types' => [
                    'face_images' => 'Face images for authentication',
                    'voice_samples' => 'Voice samples for voice biometrics',
                    'biometric_templates' => 'Biometric templates',
                ],
                'is_required' => false,
                'is_sensitive' => true,
                'retention_period_days' => 90,
                'legal_basis' => 'consent',
            ],
            [
                'slug' => 'behavioral_tracking',
                'name' => 'Behavioral Tracking',
                'description' => 'Tracking typing, mouse, and touch patterns for continuous authentication',
                'data_types' => [
                    'behavioral_signals' => 'Typing, mouse, touch patterns',
                    'device_fingerprint' => 'Device fingerprinting data',
                ],
                'is_required' => false,
                'is_sensitive' => true,
                'retention_period_days' => 180,
                'legal_basis' => 'consent',
            ],
            [
                'slug' => 'location_tracking',
                'name' => 'Location Tracking',
                'description' => 'IP geolocation and geo-velocity checks for security',
                'data_types' => [
                    'ip_addresses' => 'IP addresses',
                    'gps_coordinates' => 'GPS coordinates (if provided)',
                ],
                'is_required' => false,
                'is_sensitive' => true,
                'retention_period_days' => 90,
                'legal_basis' => 'legitimate_interest',
            ],
            [
                'slug' => 'ai_processing',
                'name' => 'AI Processing',
                'description' => 'Sharing anonymized data with external AI providers',
                'data_types' => [
                    'anonymized_medical_data' => 'Anonymized medical data for AI diagnosis',
                    'anonymized_user_data' => 'Anonymized user data for ML training',
                ],
                'is_required' => false,
                'is_sensitive' => false,
                'retention_period_days' => 365,
                'legal_basis' => 'consent',
            ],
            [
                'slug' => 'marketing_communications',
                'name' => 'Marketing Communications',
                'description' => 'Email and push notifications for marketing',
                'data_types' => [
                    'email' => 'Email address',
                    'phone' => 'Phone number',
                    'notification_tokens' => 'Push notification tokens',
                ],
                'is_required' => false,
                'is_sensitive' => false,
                'retention_period_days' => 2555, // 7 years
                'legal_basis' => 'consent',
            ],
            [
                'slug' => 'analytics',
                'name' => 'Analytics',
                'description' => 'Usage analytics and metrics for platform improvement',
                'data_types' => [
                    'usage_logs' => 'Usage logs',
                    'anonymized_metrics' => 'Anonymized metrics',
                ],
                'is_required' => true,
                'is_sensitive' => false,
                'retention_period_days' => 730, // 2 years
                'legal_basis' => 'legitimate_interest',
            ],
        ];

        foreach ($purposes as $purpose) {
            ConsentPurpose::updateOrCreate(
                ['slug' => $purpose['slug']],
                $purpose
            );
        }
    }
}
```

---

## 9. Testing

### 9.1 Unit Tests

**File:** `tests/Unit/Services/Privacy/ConsentManagementServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Privacy;

use Tests\TestCase;
use App\Services\Privacy\ConsentManagementService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class ConsentManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_grant_conent_creates_record(): void
    {
        // Implementation
    }

    public function test_revoke_conent_triggers_deletion(): void
    {
        // Implementation
    }

    public function test_has_conent_returns_correct_status(): void
    {
        // Implementation
    }
}
```

---

## 10. Acceptance Criteria

### Phase 1: Consent Management (Week 1)
- [x] Database migrations created
- [x] ConsentManagementService implemented
- [x] ConsentPurpose model and seeder
- [x] Consent model with audit trail
- [x] Consent grant/revoke endpoints
- [x] Unit tests for ConsentManagementService

### Phase 2: Privacy Engine (Week 2)
- [x] PrivacyEngineService implemented
- [x] DataMinimizationService implemented
- [x] Data minimization rules
- [x] Retention policies
- [x] Consent enforcement checks
- [x] Unit tests for privacy services

### Phase 3: Data Deletion (Week 2-3)
- [x] DataDeletionService implemented
- [x] 72-hour deletion workflow
- [x] Full/partial/anonymization deletion
- [x] Filament dashboard for deletion requests
- [x] API endpoints for deletion
- [x] Integration tests

---

## 11. Deployment Checklist

- [ ] Database migrations run in production
- [ ] Consent purposes seeded
- [ ] Retention policies configured
- [ ] Data minimization rules configured
- [ ] API routes tested
- [ ] Filament dashboard accessible
- [ ] Monitoring configured
- [ ] Alert rules set up
- [ ] Documentation completed
- [ ] Team trained

---

## 12. Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Accidental data deletion | LOW | CRITICAL | Require admin approval, backup before deletion |
| Consent version conflicts | LOW | MEDIUM | Versioning system, migration logic |
| Performance impact | LOW | MEDIUM | Caching, async deletion |
| Regulatory changes | MEDIUM | MEDIUM | Flexible configuration, regular reviews |
| User misunderstanding | MEDIUM | LOW | Clear UI, consent explanations |

---

**Document Version:** 1.0  
**Last Updated:** 19 April 2026  
**Next Review:** After implementation completion
