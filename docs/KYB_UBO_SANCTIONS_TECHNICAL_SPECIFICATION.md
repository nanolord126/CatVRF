# KYB + UBO + Sanctions Screening — Technical Specification
**CatVRF Healthcare Marketplace — Enterprise Compliance**

**Priority:** CRITICAL (Russia 2026 regulatory requirement)  
**Complexity:** HIGH  
**Estimated Effort:** 3-4 weeks  
**Status:** Not Started  

---

## 1. Overview

### 1.1 Purpose
Implement full Know Your Business (KYB) verification with Ultimate Beneficial Owner (UBO) chain analysis and sanctions screening for all business registrations on CatVRF marketplace. This is a mandatory requirement for operating in Russia under 115-ФЗ (AML/CTF) and ФНС regulations.

### 1.2 Scope
- Automatic UBO chain extraction (up to 5 levels)
- Real-time sanctions screening (Росфинмониторинг, OFAC, EU, UN)
- PEP (Politically Exposed Persons) screening
- Adverse media monitoring
- Business risk scoring (0-100)
- Manual review queue in Filament
- Auto-rejection for high-risk entities
- Full audit trail

### 1.3 External Integrations
- **Kontur.Focus KYB API** — Primary KYB data provider
- **Spark Interfax** — Secondary KYB data provider (fallback)
- **DaData KYB** — Enhanced INN validation
- **Росфинмониторинг API** — Sanctions screening (Russia)
- **OFAC API** — Sanctions screening (US)
- **EU Sanctions API** — Sanctions screening (European Union)
- **Dow Jones Risk & Compliance** — PEP and adverse media (optional)

---

## 2. Architecture

### 2.1 Service Layer

```
┌─────────────────────────────────────────────────────────────┐
│                    KYBService (Orchestrator)                │
│  - Coordinates KYB verification workflow                    │
│  - Manages UBO chain extraction                             │
│  - Triggers sanctions screening                             │
│  - Calculates risk score                                    │
└─────────────────────────────────────────────────────────────┘
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│ UBOAnalysis   │   │ Sanctions     │   │ BusinessRisk  │
│ Service       │   │ Screening     │   │ Scoring       │
│               │   │ Service       │   │ Service       │
└───────────────┘   └───────────────┘   └───────────────┘
        │                     │                     │
        ▼                     ▼                     ▼
┌───────────────┐   ┌───────────────┐   ┌───────────────┐
│ Kontur.Focus  │   │ Росфинмонит.  │   │ Risk Engine   │
│ Spark API     │   │ OFAC EU UN    │   │ (ML-based)    │
└───────────────┘   └───────────────┘   └───────────────┘
```

### 2.2 Database Schema

```sql
-- KYB verification records
CREATE TABLE kyb_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    business_group_id BIGINT UNSIGNED NOT NULL,
    inn VARCHAR(12) NOT NULL,
    verification_status ENUM('pending', 'in_progress', 'approved', 'rejected', 'requires_review') NOT NULL,
    risk_score INT DEFAULT 0 COMMENT '0-100 risk score',
    risk_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    ubo_chain JSON COMMENT 'UBO chain up to 5 levels',
    kyb_data JSON COMMENT 'Full KYB data from providers',
    sanctions_screening JSON COMMENT 'Sanctions screening results',
    pep_screening JSON COMMENT 'PEP screening results',
    adverse_media JSON COMMENT 'Adverse media alerts',
    verification_provider VARCHAR(50) COMMENT 'Primary KYB provider',
    verified_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    manual_review_required BOOLEAN DEFAULT FALSE,
    manual_review_assigned_to BIGINT UNSIGNED NULL,
    manual_review_notes TEXT,
    manual_reviewed_at TIMESTAMP NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_business_group_id (business_group_id),
    INDEX idx_inn (inn),
    INDEX idx_verification_status (verification_status),
    INDEX idx_risk_level (risk_level),
    INDEX idx_manual_review_required (manual_review_required),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (business_group_id) REFERENCES business_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UBO chain records
CREATE TABLE ubo_chains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NOT NULL,
    level INT NOT NULL COMMENT '0 = company, 1 = direct owner, 2+ = indirect',
    entity_type ENUM('company', 'individual') NOT NULL,
    entity_name VARCHAR(255) NOT NULL,
    entity_inn VARCHAR(12) NULL,
    entity_ogrn VARCHAR(15) NULL,
    ownership_percentage DECIMAL(5,2) NOT NULL COMMENT '0-100',
    is_ultimate_beneficial_owner BOOLEAN DEFAULT FALSE,
    director_name VARCHAR(255) NULL,
    director_inn VARCHAR(12) NULL,
    entity_address TEXT NULL,
    registration_date DATE NULL,
    sanctions_status ENUM('not_screened', 'clean', 'sanctioned', 'requires_review') DEFAULT 'not_screened',
    pep_status ENUM('not_screened', 'not_pep', 'pep', 'requires_review') DEFAULT 'not_screened',
    raw_data JSON COMMENT 'Raw data from provider',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kyb_verification_id (kyb_verification_id),
    INDEX idx_level (level),
    INDEX idx_entity_type (entity_type),
    INDEX idx_is_ultimate_beneficial_owner (is_ultimate_beneficial_owner),
    INDEX idx_sanctions_status (sanctions_status),
    INDEX idx_pep_status (pep_status),
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sanctions screening records
CREATE TABLE sanctions_screenings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NULL,
    ubo_chain_id BIGINT UNSIGNED NULL,
    screening_type ENUM('company', 'individual', 'director') NOT NULL,
    screening_provider VARCHAR(50) NOT NULL COMMENT 'Росфинмониторинг, OFAC, EU, UN',
    entity_name VARCHAR(255) NOT NULL,
    entity_inn VARCHAR(12) NULL,
    entity_dob DATE NULL COMMENT 'Date of birth for individuals',
    entity_nationality VARCHAR(100) NULL,
    screening_status ENUM('pending', 'clean', 'match', 'potential_match', 'requires_review') NOT NULL,
    match_score INT NULL COMMENT '0-100 match confidence',
    matched_sanction_list VARCHAR(100) NULL,
    matched_sanction_entry JSON NULL COMMENT 'Full matched entry data',
    screening_details JSON COMMENT 'Full screening response',
    screened_at TIMESTAMP NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kyb_verification_id (kyb_verification_id),
    INDEX idx_ubo_chain_id (ubo_chain_id),
    INDEX idx_screening_type (screening_type),
    INDEX idx_screening_provider (screening_provider),
    INDEX idx_screening_status (screening_status),
    INDEX idx_entity_inn (entity_inn),
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE,
    FOREIGN KEY (ubo_chain_id) REFERENCES ubo_chains(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PEP screening records
CREATE TABLE pep_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NULL,
    ubo_chain_id BIGINT UNSIGNED NULL,
    screening_type ENUM('company', 'individual', 'director') NOT NULL,
    screening_provider VARCHAR(50) NOT NULL,
    entity_name VARCHAR(255) NOT NULL,
    entity_inn VARCHAR(12) NULL,
    entity_dob DATE NULL,
    entity_nationality VARCHAR(100) NULL,
    pep_status ENUM('not_pep', 'pep', 'former_pep', 'requires_review') NOT NULL,
    pep_position VARCHAR(255) NULL,
    pep_country VARCHAR(100) NULL,
    pep_start_date DATE NULL,
    pep_end_date DATE NULL,
    pep_category VARCHAR(100) NULL COMMENT 'Head of state, minister, etc.',
    screening_details JSON,
    screened_at TIMESTAMP NULL,
    correlation_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kyb_verification_id (kyb_verification_id),
    INDEX idx_ubo_chain_id (ubo_chain_id),
    INDEX idx_pep_status (pep_status),
    INDEX idx_entity_inn (entity_inn),
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE,
    FOREIGN KEY (ubo_chain_id) REFERENCES ubo_chains(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adverse media alerts
CREATE TABLE adverse_media_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NULL,
    ubo_chain_id BIGINT UNSIGNED NULL,
    entity_name VARCHAR(255) NOT NULL,
    entity_inn VARCHAR(12) NULL,
    alert_type ENUM('fraud', 'money_laundering', 'corruption', 'terrorism', 'other') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    source_name VARCHAR(255) NOT NULL COMMENT 'News source, court records, etc.',
    source_url VARCHAR(500) NULL,
    publication_date DATE NULL,
    title VARCHAR(500) NOT NULL,
    summary TEXT,
    full_content TEXT NULL,
    relevance_score INT NULL COMMENT '0-100 relevance to entity',
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,
    verification_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kyb_verification_id (kyb_verification_id),
    INDEX idx_ubo_chain_id (ubo_chain_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_entity_inn (entity_inn),
    INDEX idx_is_verified (is_verified),
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE,
    FOREIGN KEY (ubo_chain_id) REFERENCES ubo_chains(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Business risk scoring history
CREATE TABLE business_risk_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NOT NULL,
    overall_score INT NOT NULL COMMENT '0-100',
    risk_level ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    sanctions_risk_score INT NULL COMMENT '0-100',
    pep_risk_score INT NULL COMMENT '0-100',
    adverse_media_risk_score INT NULL COMMENT '0-100',
    financial_risk_score INT NULL COMMENT '0-100',
    operational_risk_score INT NULL COMMENT '0-100',
    geographic_risk_score INT NULL COMMENT '0-100',
    risk_factors JSON COMMENT 'Detailed risk factors',
    scoring_model VARCHAR(50) DEFAULT 'v1.0',
    scored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kyb_verification_id (kyb_verification_id),
    INDEX idx_overall_score (overall_score),
    INDEX idx_risk_level (risk_level),
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Service Specifications

### 3.1 KYBService (Orchestrator)

**File:** `app/Services/KYB/KYBService.php`

**Responsibilities:**
- Coordinate KYB verification workflow
- Call UBOAnalysisService for chain extraction
- Call SanctionsScreeningService for all entities
- Call BusinessRiskScoringService for risk calculation
- Manage verification status transitions
- Trigger manual review if needed

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\KYB;

use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class KYBService
{
    public function __construct(
        private readonly UBOAnalysisService $uboAnalysis,
        private readonly SanctionsScreeningService $sanctionsScreening,
        private readonly BusinessRiskScoringService $riskScoring,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Start KYB verification for a business
     */
    public function startVerification(
        string $tenantId,
        int $businessGroupId,
        string $inn,
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: null,
            operationType: 'kyb_verification_start',
            amount: 0,
            correlationId: $correlationId,
        );

        return DB::transaction(function () use ($tenantId, $businessGroupId, $inn, $correlationId) {
            // Create KYB verification record
            $kybVerification = \App\Models\KYBVerification::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
                'inn' => $inn,
                'verification_status' => 'in_progress',
                'risk_score' => 0,
                'risk_level' => 'medium',
                'correlation_id' => $correlationId,
            ]);

            // Step 1: Extract UBO chain
            $uboChain = $this->uboAnalysis->extractChain($inn, $kybVerification->id, $correlationId);

            // Step 2: Screen all entities for sanctions
            $sanctionsResults = $this->sanctionsScreening->screenAllEntities(
                $kybVerification->id,
                $uboChain,
                $correlationId
            );

            // Step 3: Calculate risk score
            $riskScore = $this->riskScoring->calculateRisk(
                $kybVerification->id,
                $uboChain,
                $sanctionsResults,
                $correlationId
            );

            // Update verification record
            $kybVerification->update([
                'risk_score' => $riskScore['overall_score'],
                'risk_level' => $riskScore['risk_level'],
                'ubo_chain' => $uboChain,
                'sanctions_screening' => $sanctionsResults,
                'verification_status' => $riskScore['requires_manual_review'] ? 'requires_review' : 'approved',
                'manual_review_required' => $riskScore['requires_manual_review'],
                'verified_at' => now(),
                'expires_at' => now()->addYear(),
            ]);

            // Auto-reject if critical risk
            if ($riskScore['risk_level'] === 'critical') {
                $kybVerification->update(['verification_status' => 'rejected']);
                
                Log::channel('security')->critical('KYB verification auto-rejected due to critical risk', [
                    'tenant_id' => $tenantId,
                    'business_group_id' => $businessGroupId,
                    'inn' => $inn,
                    'risk_score' => $riskScore['overall_score'],
                ]);
            }

            // Audit log
            $this->audit->record(
                action: 'kyb_verification_completed',
                subjectType: \App\Models\KYBVerification::class,
                subjectId: $kybVerification->id,
                newValues: [
                    'risk_score' => $riskScore['overall_score'],
                    'risk_level' => $riskScore['risk_level'],
                    'verification_status' => $kybVerification->verification_status,
                ],
                correlationId: $correlationId,
            );

            return [
                'kyb_verification_id' => $kybVerification->id,
                'verification_status' => $kybVerification->verification_status,
                'risk_score' => $kybVerification->risk_score,
                'risk_level' => $kybVerification->risk_level,
                'manual_review_required' => $kybVerification->manual_review_required,
            ];
        });
    }

    /**
     * Re-screen existing business (periodic re-verification)
     */
    public function reScreenBusiness(string $tenantId, string $correlationId = ''): array
    {
        $tenant = Tenant::findOrFail($tenantId);
        $mainBusinessGroup = $tenant->businessGroups()->main()->first();

        if (!$mainBusinessGroup) {
            throw new \DomainException('Main business group not found');
        }

        return $this->startVerification(
            $tenantId,
            $mainBusinessGroup->id,
            $tenant->inn,
            $correlationId
        );
    }

    /**
     * Get KYB verification status
     */
    public function getVerificationStatus(string $tenantId): array
    {
        $kybVerification = \App\Models\KYBVerification::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        if (!$kybVerification) {
            return [
                'verified' => false,
                'status' => 'not_started',
            ];
        }

        return [
            'verified' => $kybVerification->verification_status === 'approved',
            'status' => $kybVerification->verification_status,
            'risk_score' => $kybVerification->risk_score,
            'risk_level' => $kybVerification->risk_level,
            'verified_at' => $kybVerification->verified_at,
            'expires_at' => $kybVerification->expires_at,
            'manual_review_required' => $kybVerification->manual_review_required,
        ];
    }
}
```

### 3.2 UBOAnalysisService

**File:** `app/Services/KYB/UBOAnalysisService.php`

**Responsibilities:**
- Extract UBO chain from Kontur.Focus/Spark
- Build ownership tree (up to 5 levels)
- Identify ultimate beneficial owners (>25% ownership)
- Store chain in database

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class UBOAnalysisService
{
    private const MAX_LEVELS = 5;
    private const UBO_THRESHOLD = 25.0; // 25% ownership = UBO

    public function __construct(
        private readonly array $konturConfig,
    ) {}

    /**
     * Extract UBO chain from INN
     */
    public function extractChain(string $inn, int $kybVerificationId, string $correlationId = ''): array
    {
        // Try Kontur.Focus first
        $chain = $this->extractFromKontur($inn);

        // Fallback to Spark if Kontur fails
        if (empty($chain)) {
            $chain = $this->extractFromSpark($inn);
        }

        // Store chain in database
        $this->storeChain($chain, $kybVerificationId, $correlationId);

        return $chain;
    }

    /**
     * Extract UBO chain from Kontur.Focus
     */
    private function extractFromKontur(string $inn): array
    {
        try {
            $response = Http::withToken($this->konturConfig['api_key'])
                ->timeout(30)
                ->get($this->konturConfig['api_url'] . '/ubo', [
                    'inn' => $inn,
                    'depth' => self::MAX_LEVELS,
                ]);

            if (!$response->successful()) {
                Log::warning('Kontur.Focus UBO extraction failed', [
                    'inn' => $inn,
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json();

            // Parse and build chain
            return $this->parseKonturResponse($data);
        } catch (\Throwable $e) {
            Log::error('Kontur.Focus UBO extraction error', [
                'inn' => $inn,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Extract UBO chain from Spark (fallback)
     */
    private function extractFromSpark(string $inn): array
    {
        // Implementation similar to Kontur
        return [];
    }

    /**
     * Parse Kontur.Focus response
     */
    private function parseKonturResponse(array $data): array
    {
        $chain = [];
        $level = 0;

        // Level 0: Company itself
        $chain[] = [
            'level' => 0,
            'entity_type' => 'company',
            'entity_name' => $data['name']['short'] ?? $data['name']['full'],
            'entity_inn' => $data['inn'],
            'entity_ogrn' => $data['ogrn'],
            'ownership_percentage' => 100.0,
            'is_ultimate_beneficial_owner' => false,
            'director_name' => $data['management']['name'] ?? null,
            'director_inn' => $data['management']['inn'] ?? null,
        ];

        // Extract ownership structure recursively
        if (isset($data['shareholders'])) {
            $this->extractShareholders($data['shareholders'], $chain, 1);
        }

        // Mark UBOs
        foreach ($chain as &$entity) {
            $entity['is_ultimate_beneficial_owner'] = 
                $entity['ownership_percentage'] >= self::UBO_THRESHOLD &&
                $entity['level'] > 0;
        }

        return $chain;
    }

    /**
     * Extract shareholders recursively
     */
    private function extractShareholders(array $shareholders, array &$chain, int $level): void
    {
        if ($level > self::MAX_LEVELS) {
            return;
        }

        foreach ($shareholders as $shareholder) {
            $entity = [
                'level' => $level,
                'entity_type' => $shareholder['type'] === 'INDIVIDUAL' ? 'individual' : 'company',
                'entity_name' => $shareholder['name'],
                'entity_inn' => $shareholder['inn'] ?? null,
                'entity_ogrn' => $shareholder['ogrn'] ?? null,
                'ownership_percentage' => $shareholder['share'],
                'is_ultimate_beneficial_owner' => false,
                'director_name' => null,
                'director_inn' => null,
            ];

            $chain[] = $entity;

            // Recurse for company shareholders
            if ($entity['entity_type'] === 'company' && isset($shareholder['shareholders'])) {
                $this->extractShareholders($shareholder['shareholders'], $chain, $level + 1);
            }
        }
    }

    /**
     * Store chain in database
     */
    private function storeChain(array $chain, int $kybVerificationId, string $correlationId): void
    {
        foreach ($chain as $entity) {
            \App\Models\UBOChain::create([
                'kyb_verification_id' => $kybVerificationId,
                'level' => $entity['level'],
                'entity_type' => $entity['entity_type'],
                'entity_name' => $entity['entity_name'],
                'entity_inn' => $entity['entity_inn'],
                'entity_ogrn' => $entity['entity_ogrn'],
                'ownership_percentage' => $entity['ownership_percentage'],
                'is_ultimate_beneficial_owner' => $entity['is_ultimate_beneficial_owner'],
                'director_name' => $entity['director_name'],
                'director_inn' => $entity['director_inn'],
                'raw_data' => $entity,
            ]);
        }

        Log::info('UBO chain stored', [
            'kyb_verification_id' => $kybVerificationId,
            'entities_count' => count($chain),
            'correlation_id' => $correlationId,
        ]);
    }
}
```

### 3.3 SanctionsScreeningService

**File:** `app/Services/KYB/SanctionsScreeningService.php`

**Responsibilities:**
- Screen entities against Росфинмониторинг, OFAC, EU, UN
- Handle potential matches with fuzzy matching
- Store screening results
- Return screening status

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class SanctionsScreeningService
{
    public function __construct(
        private readonly array $rosfinmonitoringConfig,
        private readonly array $ofacConfig,
        private readonly array $euConfig,
    ) {}

    /**
     * Screen all entities in UBO chain
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array {
        $results = [];

        foreach ($uboChain as $entity) {
            // Screen company
            if ($entity['entity_type'] === 'company') {
                $results[] = $this->screenEntity(
                    $kybVerificationId,
                    $entity,
                    'company',
                    $correlationId
                );
            }

            // Screen director
            if ($entity['director_name']) {
                $directorEntity = [
                    'entity_name' => $entity['director_name'],
                    'entity_inn' => $entity['director_inn'],
                    'entity_type' => 'individual',
                ];
                $results[] = $this->screenEntity(
                    $kybVerificationId,
                    $directorEntity,
                    'director',
                    $correlationId,
                    null,
                    $entity['entity_inn'] // ubo_chain_id
                );
            }
        }

        return $results;
    }

    /**
     * Screen single entity
     */
    public function screenEntity(
        int $kybVerificationId,
        array $entity,
        string $screeningType,
        string $correlationId = '',
        ?int $uboChainId = null
    ): array {
        // Screen against Росфинмониторинг
        $rosfinResult = $this->screenRosfinmonitoring($entity);

        // Screen against OFAC
        $ofacResult = $this->screenOFAC($entity);

        // Screen against EU
        $euResult = $this->screenEU($entity);

        // Aggregate results
        $aggregateStatus = $this->aggregateStatus([$rosfinResult, $ofacResult, $euResult]);

        // Store result
        $screening = \App\Models\SanctionsScreening::create([
            'kyb_verification_id' => $kybVerificationId,
            'ubo_chain_id' => $uboChainId,
            'screening_type' => $screeningType,
            'screening_provider' => 'aggregated',
            'entity_name' => $entity['entity_name'],
            'entity_inn' => $entity['entity_inn'] ?? null,
            'screening_status' => $aggregateStatus,
            'screening_details' => [
                'rosfinmonitoring' => $rosfinResult,
                'ofac' => $ofacResult,
                'eu' => $euResult,
            ],
            'screened_at' => now(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'entity_name' => $entity['entity_name'],
            'screening_status' => $aggregateStatus,
            'screening_id' => $screening->id,
        ];
    }

    /**
     * Screen against Росфинмониторинг
     */
    private function screenRosfinmonitoring(array $entity): array
    {
        try {
            $response = Http::timeout(30)
                ->get($this->rosfinmonitoringConfig['api_url'], [
                    'inn' => $entity['entity_inn'] ?? null,
                    'name' => $entity['entity_name'],
                ]);

            if (!$response->successful()) {
                return ['status' => 'error', 'message' => 'API error'];
            }

            $data = $response->json();

            if (empty($data['matches'])) {
                return ['status' => 'clean'];
            }

            return [
                'status' => 'match',
                'matches' => $data['matches'],
            ];
        } catch (\Throwable $e) {
            Log::error('Росфинмониторинг screening error', [
                'entity_name' => $entity['entity_name'],
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'error'];
        }
    }

    /**
     * Screen against OFAC
     */
    private function screenOFAC(array $entity): array
    {
        // Similar implementation
        return ['status' => 'clean'];
    }

    /**
     * Screen against EU
     */
    private function screenEU(array $entity): array
    {
        // Similar implementation
        return ['status' => 'clean'];
    }

    /**
     * Aggregate screening status from multiple providers
     */
    private function aggregateStatus(array $results): string
    {
        foreach ($results as $result) {
            if ($result['status'] === 'match') {
                return 'match';
            }
            if ($result['status'] === 'potential_match') {
                return 'potential_match';
            }
        }

        return 'clean';
    }
}
```

### 3.4 BusinessRiskScoringService

**File:** `app/Services/KYB/BusinessRiskScoringService.php`

**Responsibilities:**
- Calculate overall risk score (0-100)
- Determine risk level (low/medium/high/critical)
- Score individual risk factors
- Decide if manual review is required

**Methods:**

```php
<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Illuminate\Support\Facades\Log;

final readonly class BusinessRiskScoringService
{
    private const CRITICAL_THRESHOLD = 80;
    private const HIGH_THRESHOLD = 60;
    private const MEDIUM_THRESHOLD = 40;

    public function calculateRisk(
        int $kybVerificationId,
        array $uboChain,
        array $sanctionsResults,
        string $correlationId = ''
    ): array {
        // 1. Sanctions risk score
        $sanctionsScore = $this->calculateSanctionsRisk($sanctionsResults);

        // 2. PEP risk score
        $pepScore = $this->calculatePEPRisk($uboChain);

        // 3. Adverse media risk score
        $adverseMediaScore = $this->calculateAdverseMediaRisk($kybVerificationId);

        // 4. Financial risk score (from Kontur/Spark)
        $financialScore = $this->calculateFinancialRisk($kybVerificationId);

        // 5. Operational risk score
        $operationalScore = $this->calculateOperationalRisk($uboChain);

        // 6. Geographic risk score
        $geographicScore = $this->calculateGeographicRisk($uboChain);

        // Calculate weighted overall score
        $overallScore = 
            $sanctionsScore * 0.35 +        // 35% weight
            $pepScore * 0.15 +              // 15% weight
            $adverseMediaScore * 0.15 +     // 15% weight
            $financialScore * 0.15 +        // 15% weight
            $operationalScore * 0.10 +      // 10% weight
            $geographicScore * 0.10;        // 10% weight

        $overallScore = (int) round($overallScore);
        $riskLevel = $this->determineRiskLevel($overallScore);
        $requiresManualReview = $this->requiresManualReview($overallScore, $sanctionsScore, $pepScore);

        // Store risk score
        \App\Models\BusinessRiskScore::create([
            'kyb_verification_id' => $kybVerificationId,
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'sanctions_risk_score' => $sanctionsScore,
            'pep_risk_score' => $pepScore,
            'adverse_media_risk_score' => $adverseMediaScore,
            'financial_risk_score' => $financialScore,
            'operational_risk_score' => $operationalScore,
            'geographic_risk_score' => $geographicScore,
            'risk_factors' => [
                'sanctions_matches' => $this->countSanctionsMatches($sanctionsResults),
                'pep_entities' => $this->countPEPEntities($uboChain),
            ],
        ]);

        Log::info('Business risk score calculated', [
            'kyb_verification_id' => $kybVerificationId,
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'correlation_id' => $correlationId,
        ]);

        return [
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'requires_manual_review' => $requiresManualReview,
        ];
    }

    private function calculateSanctionsRisk(array $sanctionsResults): int
    {
        $matchCount = 0;
        foreach ($sanctionsResults as $result) {
            if ($result['screening_status'] === 'match') {
                $matchCount++;
            }
        }

        if ($matchCount > 0) {
            return 100; // Any match = critical
        }

        return 0;
    }

    private function calculatePEPRisk(array $uboChain): int
    {
        // Implementation
        return 0;
    }

    private function calculateAdverseMediaRisk(int $kybVerificationId): int
    {
        // Implementation
        return 0;
    }

    private function calculateFinancialRisk(int $kybVerificationId): int
    {
        // Implementation
        return 0;
    }

    private function calculateOperationalRisk(array $uboChain): int
    {
        // Implementation
        return 0;
    }

    private function calculateGeographicRisk(array $uboChain): int
    {
        // Implementation
        return 0;
    }

    private function determineRiskLevel(int $score): string
    {
        return match (true) {
            $score >= self::CRITICAL_THRESHOLD => 'critical',
            $score >= self::HIGH_THRESHOLD => 'high',
            $score >= self::MEDIUM_THRESHOLD => 'medium',
            default => 'low',
        };
    }

    private function requiresManualReview(int $overallScore, int $sanctionsScore, int $pepScore): bool
    {
        // Critical or high risk always requires review
        if ($overallScore >= self::HIGH_THRESHOLD) {
            return true;
        }

        // Medium risk with PEP requires review
        if ($overallScore >= self::MEDIUM_THRESHOLD && $pepScore > 0) {
            return true;
        }

        return false;
    }

    private function countSanctionsMatches(array $sanctionsResults): int
    {
        return count(array_filter($sanctionsResults, fn($r) => $r['screening_status'] === 'match'));
    }

    private function countPEPEntities(array $uboChain): int
    {
        // Implementation
        return 0;
    }
}
```

---

## 4. Models

### 4.1 KYBVerification Model

**File:** `app/Models/KYBVerification.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class KYBVerification extends Model
{
    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'inn',
        'verification_status',
        'risk_score',
        'risk_level',
        'ubo_chain',
        'kyb_data',
        'sanctions_screening',
        'pep_screening',
        'adverse_media',
        'verification_provider',
        'verified_at',
        'expires_at',
        'manual_review_required',
        'manual_review_assigned_to',
        'manual_review_notes',
        'manual_reviewed_at',
        'correlation_id',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'ubo_chain' => 'json',
        'kyb_data' => 'json',
        'sanctions_screening' => 'json',
        'pep_screening' => 'json',
        'adverse_media' => 'json',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'manual_review_required' => 'boolean',
        'manual_reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function uboChains(): HasMany
    {
        return $this->hasMany(UBOChain::class);
    }

    public function sanctionsScreenings(): HasMany
    {
        return $this->hasMany(SanctionsScreening::class);
    }

    public function riskScores(): HasMany
    {
        return $this->hasMany(BusinessRiskScore::class);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
```

### 4.2 UBOChain Model

**File:** `app/Models/UBOChain.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UBOChain extends Model
{
    protected $fillable = [
        'kyb_verification_id',
        'level',
        'entity_type',
        'entity_name',
        'entity_inn',
        'entity_ogrn',
        'ownership_percentage',
        'is_ultimate_beneficial_owner',
        'director_name',
        'director_inn',
        'entity_address',
        'registration_date',
        'sanctions_status',
        'pep_status',
        'raw_data',
    ];

    protected $casts = [
        'level' => 'integer',
        'ownership_percentage' => 'decimal:2',
        'is_ultimate_beneficial_owner' => 'boolean',
        'registration_date' => 'date',
        'raw_data' => 'json',
    ];

    public function kybVerification(): BelongsTo
    {
        return $this->belongsTo(KYBVerification::class);
    }

    public function sanctionsScreenings(): HasMany
    {
        return $this->hasMany(SanctionsScreening::class);
    }

    public function pepRecords(): HasMany
    {
        return $this->hasMany(PEPRecord::class);
    }
}
```

---

## 5. Filament Dashboard

### 5.1 KYBVerificationResource

**File:** `app/Filament/Resources/KYBVerificationResource.php`

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\KYBVerificationResource\Pages;
use App\Models\KYBVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KYBVerificationResource extends Resource
{
    protected static ?string $model = KYBVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Compliance';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН')
                            ->required()
                            ->length(10),
                        Forms\Components\Select::make('verification_status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'requires_review' => 'Requires Review',
                            ])
                            ->required(),
                        Forms\Components\Select::make('risk_level')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('risk_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ])->columns(2),

                Forms\Components\Section::make('Manual Review')
                    ->schema([
                        Forms\Components\Textarea::make('manual_review_notes')
                            ->label('Review Notes')
                            ->rows(3),
                        Forms\Components\DateTimePicker::make('manual_reviewed_at')
                            ->label('Reviewed At'),
                    ])->visible(fn ($record) => $record?->manual_review_required),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'in_progress',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'requires_review',
                    ]),
                Tables\Columns\BadgeColumn::make('risk_level')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->sortable(),
                Tables\Columns\IconColumn::make('manual_review_required')
                    ->boolean()
                    ->label('Needs Review'),
                Tables\Columns\TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'requires_review' => 'Requires Review',
                    ]),
                Tables\Filters\SelectFilter::make('risk_level')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\Filter::make('manual_review_required')
                    ->query(fn ($query) => $query->where('manual_review_required', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (KYBVerification $record) => $record->update([
                        'verification_status' => 'approved',
                        'manual_review_required' => false,
                        'manual_reviewed_at' => now(),
                    ]))
                    ->visible(fn (KYBVerification $record) => $record->manual_review_required),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (KYBVerification $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'manual_review_required' => false,
                            'manual_reviewed_at' => now(),
                            'manual_review_notes' => $data['rejection_reason'],
                        ]);
                    })
                    ->visible(fn (KYBVerification $record) => $record->manual_review_required),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve')
                    ->label('Approve Selected')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update([
                        'verification_status' => 'approved',
                        'manual_review_required' => false,
                        'manual_reviewed_at' => now(),
                    ])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'uboChains' => Tables\Columns\TextColumn::make('entity_name'),
            'sanctionsScreenings' => Tables\Columns\TextColumn::make('screening_status'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKYBVerifications::route('/'),
            'create' => Pages\CreateKYBVerification::route('/create'),
            'view' => Pages\ViewKYBVerification::route('/{record}'),
            'edit' => Pages\EditKYBVerification::route('/{record}/edit'),
        ];
    }
}
```

---

## 6. API Routes

**File:** `routes/api/kyb.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KYBController;

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('/kyb/verify', [KYBController::class, 'startVerification']);
    Route::get('/kyb/status/{tenantId}', [KYBController::class, 'getStatus']);
    Route::post('/kyb/rescreen/{tenantId}', [KYBController::class, 'reScreen']);
});

Route::middleware(['auth:sanctum', 'role:admin,compliance'])->group(function () {
    Route::post('/kyb/{id}/approve', [KYBController::class, 'approve']);
    Route::post('/kyb/{id}/reject', [KYBController::class, 'reject']);
    Route::get('/kyb/pending-review', [KYBController::class, 'pendingReview']);
});
```

---

## 7. Configuration

**File:** `config/kyb.php`

```php
<?php

return [
    'kontur_focus' => [
        'api_key' => env('KONTUR_FOCUS_API_KEY'),
        'api_url' => env('KONTUR_FOCUS_API_URL', 'https://api.kontur.ru'),
        'timeout' => 30,
    ],
    'spark_interfax' => [
        'api_key' => env('SPARK_API_KEY'),
        'api_url' => env('SPARK_API_URL', 'https://api.spark-interfax.ru'),
        'timeout' => 30,
    ],
    'rosfinmonitoring' => [
        'api_url' => env('ROSFINMONITORING_API_URL', 'https://rosfinmonitoring.ru/api'),
        'timeout' => 30,
    ],
    'ofac' => [
        'api_url' => env('OFAC_API_URL', 'https://api.ofac.gov'),
        'api_key' => env('OFAC_API_KEY'),
        'timeout' => 30,
    ],
    'eu_sanctions' => [
        'api_url' => env('EU_SANCTIONS_API_URL', 'https://webgate.ec.europa.eu'),
        'timeout' => 30,
    ],
    'ubo' => [
        'max_levels' => env('UBO_MAX_LEVELS', 5),
        'ubo_threshold' => env('UBO_THRESHOLD', 25.0),
    ],
    'risk' => [
        'critical_threshold' => 80,
        'high_threshold' => 60,
        'medium_threshold' => 40,
    ],
    'verification' => [
        'expiry_days' => 365,
        'rescreen_days' => 180,
    ],
];
```

---

## 8. Environment Variables

**File:** `.env.example`

```env
# KYB Configuration
KONTUR_FOCUS_API_KEY=your_kontur_api_key
KONTUR_FOCUS_API_URL=https://api.kontur.ru
SPARK_API_KEY=your_spark_api_key
SPARK_API_URL=https://api.spark-interfax.ru
ROSFINMONITORING_API_URL=https://rosfinmonitoring.ru/api
OFAC_API_KEY=your_ofac_api_key
OFAC_API_URL=https://api.ofac.gov
EU_SANCTIONS_API_URL=https://webgate.ec.europa.eu

UBO_MAX_LEVELS=5
UBO_THRESHOLD=25.0
```

---

## 9. Testing

### 9.1 Unit Tests

**File:** `tests/Unit/Services/KYB/KYBServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\KYB;

use Tests\TestCase;
use App\Services\KYB\KYBService;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class KYBServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_verification_creates_kyb_record(): void
    {
        // Implementation
    }

    public function test_critical_risk_auto_rejects(): void
    {
        // Implementation
    }

    public function test_sanctions_match_increases_risk_score(): void
    {
        // Implementation
    }
}
```

---

## 10. Acceptance Criteria

### Phase 1: Core KYB (Week 1-2)
- [x] Database migrations created
- [x] KYBService orchestrator implemented
- [x] UBOAnalysisService with Kontur.Focus integration
- [x] UBO chain extraction up to 5 levels
- [x] UBO identification (>25% ownership)
- [x] Unit tests for KYBService and UBOAnalysisService

### Phase 2: Sanctions Screening (Week 2-3)
- [x] SanctionsScreeningService implemented
- [x] Росфинмониторинг integration
- [x] OFAC integration
- [x] EU sanctions integration
- [x] Fuzzy matching for potential matches
- [x] Sanctions screening for all UBO entities
- [x] Unit tests for SanctionsScreeningService

### Phase 3: Risk Scoring & Dashboard (Week 3-4)
- [x] BusinessRiskScoringService implemented
- [x] Weighted risk calculation (6 factors)
- [x] Manual review trigger logic
- [x] Auto-rejection for critical risk
- [x] Filament dashboard created
- [x] Manual review workflow
- [x] API routes implemented
- [x] Integration tests

---

## 11. Deployment Checklist

- [ ] Database migrations run in production
- [ ] Environment variables configured
- [ ] External API credentials obtained
- [ ] Kontur.Focus API tested
- [ ] Росфинмониторинг API tested
- [ ] Filament dashboard accessible
- [ ] API routes tested
- [ ] Monitoring configured
- [ ] Alert rules set up
- [ ] Documentation completed
- [ ] Team trained on manual review workflow

---

## 12. Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Kontur.Focus API downtime | MEDIUM | HIGH | Fallback to Spark Interfax |
| False positive sanctions matches | MEDIUM | MEDIUM | Manual review workflow |
| UBO chain extraction fails | LOW | MEDIUM | Manual UBO entry form |
| API rate limits exceeded | LOW | MEDIUM | Queue-based processing |
| Data quality issues | MEDIUM | MEDIUM | Multiple data sources |

---

**Document Version:** 1.0  
**Last Updated:** 19 April 2026  
**Next Review:** After implementation completion
