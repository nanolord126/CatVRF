# Technical Specification: KYB + UBO + Sanctions Screening Implementation
## Gap #1 - CRITICAL BLOCKER for B2B Seller Onboarding

**Document Version:** 1.0  
**Date:** April 19, 2026  
**Priority:** P0 (BLOCKER)  
**Estimated Effort:** 3-4 weeks  
**Complexity:** HIGH

---

## 1. Executive Summary

This specification details the implementation of missing KYB (Know Your Business) verification services required for regulatory compliance in Russia (ФНС, ЦБ РФ, 115-ФЗ). The existing KYBService is a skeleton that calls non-existent dependency services. This implementation will make KYB verification functional for B2B seller onboarding.

**Critical Issue:** Current KYBService will FAIL at runtime - all 5 dependency services are missing.

---

## 2. Current State Analysis

### Existing Components
- ✅ `KYBService.php` - Orchestrator service with workflow logic
- ✅ `KYBVerification` model - Database schema with fields for UBO, sanctions, PEP, adverse media
- ✅ `BusinessRiskScoringService.php` - Risk scoring with placeholder methods
- ✅ `KYBVerificationResource.php` - Filament dashboard (non-functional without services)

### Missing Components (To Be Implemented)
- ❌ `UBOAnalysisService` - Extract ownership chain from ЕГРЮЛ
- ❌ `SanctionsScreeningService` - Screen against Росфинмониторинг, OFAC, EU sanctions
- ❌ `PEPScreeningService` - Politically Exposed Persons detection
- ❌ `AdverseMediaScreeningService` - Negative news/reputation screening
- ❌ `LinkAnalysisService` - Ownership graph pattern detection

---

## 3. Service Specifications

### 3.1 UBOAnalysisService

**Purpose:** Extract and analyze Ultimate Beneficial Owner chain from Russian business registry (ЕГРЮЛ).

**Dependencies:**
- Kontur.Focus API (https://focus.kontur.ru/)
- Spark Interfax API (alternative)
- DaData API (for basic INN validation)

**Methods:**

```php
final class UBOAnalysisService
{
    /**
     * Extract UBO chain from ЕГРЮЛ
     * 
     * @param string $inn Company INN
     * @param int $kybVerificationId KYB verification record ID
     * @param string $correlationId Correlation ID for audit
     * @return array UBO chain with ownership percentages
     */
    public function extractChain(
        string $inn,
        int $kybVerificationId,
        string $correlationId = ''
    ): array;
    
    /**
     * Identify ultimate beneficial owners (>25% ownership)
     * 
     * @param array $uboChain Raw ownership chain
     * @return array Ultimate beneficial owners with details
     */
    public function identifyUltimateBeneficialOwners(array $uboChain): array;
    
    /**
     * Validate ownership structure for shell companies
     * 
     * @param array $uboChain Ownership chain
     * @return array Shell company detection results
     */
    public function detectShellCompanies(array $uboChain): array;
}
```

**Data Structure:**

```php
[
    'company' => [
        'inn' => '1234567890',
        'ogrn' => '1234567890123',
        'name' => 'ООО "Пример"',
        'address' => 'Москва, ул. Примерная, 1',
        'registration_date' => '2020-01-01',
        'status' => 'active',
    ],
    'owners' => [
        [
            'type' => 'individual', // or 'company'
            'inn' => '987654321098',
            'name' => 'Иванов Иван Иванович',
            'ownership_percentage' => 75.0,
            'role' => 'director',
            'is_ultimate_beneficial_owner' => true,
        ],
        [
            'type' => 'company',
            'inn' => '111222333444',
            'name' => 'ООО "Холдинг"',
            'ownership_percentage' => 25.0,
            'is_ultimate_beneficial_owner' => false,
            'nested_owners' => [
                // Recursive structure for nested ownership
            ],
        ],
    ],
    'ultimate_beneficial_owners' => [
        // Extracted UBOs with >25% ownership
    ],
    'shell_company_risk' => 'low', // low, medium, high
    'complexity_score' => 3, // 1-10 based on ownership depth
]
```

**External API Integration (Kontur.Focus):**

```php
// API Endpoint: https://focus-api.kontur.ru/api3/req
// Authentication: API Key in header
// Rate Limit: 100 requests/minute

private function fetchFromKontur(string $inn): array
{
    $response = Http::withToken(config('services.kontur.api_key'))
        ->timeout(10)
        ->get('https://focus-api.kontur.ru/api3/req', [
            'inn' => $inn,
            'key' => config('services.kontur.api_key'),
        ]);
    
    if (!$response->successful()) {
        throw new KYBException("Kontur API error: {$response->status()}");
    }
    
    return $response->json();
}
```

---

### 3.2 SanctionsScreeningService

**Purpose:** Screen all entities (company + UBOs) against sanctions lists.

**Dependencies:**
- Росфинмониторинг API (https://www.fedsfm.ru/)
- OFAC Sanctions List (US Treasury)
- EU Sanctions List
- UN Sanctions List

**Methods:**

```php
final class SanctionsScreeningService
{
    /**
     * Screen all entities in UBO chain against sanctions
     * 
     * @param int $kybVerificationId KYB verification ID
     * @param array $uboChain UBO chain from UBOAnalysisService
     * @param string $correlationId Correlation ID
     * @return array Screening results for all entities
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array;
    
    /**
     * Screen single entity against all sanctions lists
     * 
     * @param array $entity Entity data (name, INN, etc.)
     * @return array Screening result
     */
    public function screenEntity(array $entity): array;
    
    /**
     * Check against Росфинмониторинг list
     * 
     * @param string $inn Entity INN
     * @param string $name Entity name
     * @return array Match result
     */
    public function checkRosfinmonitoring(string $inn, string $name): array;
    
    /**
     * Check against OFAC sanctions list
     * 
     * @param string $name Entity name
     * @return array Match result
     */
    public function checkOFAC(string $name): array;
    
    /**
     * Check against EU sanctions list
     * 
     * @param string $name Entity name
     * @return array Match result
     */
    public function checkEU(string $name): array;
}
```

**Data Structure:**

```php
[
    [
        'entity_type' => 'company', // or 'individual'
        'entity_name' => 'ООО "Пример"',
        'entity_inn' => '1234567890',
        'screening_results' => [
            'rosfinmonitoring' => [
                'screening_status' => 'no_match', // no_match, match, potential_match
                'match_details' => null,
                'screened_at' => '2026-04-19T10:00:00Z',
            ],
            'ofac' => [
                'screening_status' => 'no_match',
                'match_details' => null,
                'screened_at' => '2026-04-19T10:00:00Z',
            ],
            'eu' => [
                'screening_status' => 'no_match',
                'match_details' => null,
                'screened_at' => '2026-04-19T10:00:00Z',
            ],
            'un' => [
                'screening_status' => 'no_match',
                'match_details' => null,
                'screened_at' => '2026-04-19T10:00:00Z',
            ],
        ],
        'overall_status' => 'clear', // clear, match, potential_match
        'requires_manual_review' => false,
    ],
    // ... more entities
]
```

**Fuzzy Matching Algorithm:**

```php
private function fuzzyMatch(string $searchName, string $listName): float
{
    // Use Levenshtein distance for fuzzy matching
    $distance = levenshtein(
        mb_strtolower($searchName),
        mb_strtolower($listName)
    );
    
    $maxLength = max(mb_strlen($searchName), mb_strlen($listName));
    $similarity = 1 - ($distance / $maxLength);
    
    return $similarity; // 0.0 to 1.0
}

// Match if similarity > 0.85
```

---

### 3.3 PEPScreeningService

**Purpose:** Detect Politically Exposed Persons in UBO chain.

**Dependencies:**
- World-Check API (Refinitiv)
- Dow Jones Risk & Compliance
- PEP database (Russian government officials)

**Methods:**

```php
final class PEPScreeningService
{
    /**
     * Screen all entities for PEP status
     * 
     * @param int $kybVerificationId KYB verification ID
     * @param array $uboChain UBO chain
     * @param string $correlationId Correlation ID
     * @return array PEP screening results
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array;
    
    /**
     * Screen individual for PEP status
     * 
     * @param array $individual Individual data
     * @return array PEP result
     */
    public function screenIndividual(array $individual): array;
    
    /**
     * Classify PEP level
     * 
     * @param array $pepData PEP data from external API
     * @return string PEP level (domestic, foreign, international, former)
     */
    public function classifyPEPLevel(array $pepData): string;
}
```

**Data Structure:**

```php
[
    [
        'individual_name' => 'Иванов Иван Иванович',
        'individual_inn' => '987654321098',
        'pep_status' => 'pep', // pep, former_pep, no_pep
        'pep_level' => 'domestic', // domestic, foreign, international
        'pep_role' => 'Minister of Health',
        'pep_country' => 'Russia',
        'pep_period' => '2020-2024',
        'family_associates' => [
            // Family members with PEP status
        ],
        'risk_level' => 'high', // low, medium, high
        'requires_enhanced_due_diligence' => true,
        'screened_at' => '2026-04-19T10:00:00Z',
    ],
]
```

---

### 3.4 AdverseMediaScreeningService

**Purpose:** Screen for negative news/reputation about entities.

**Dependencies:**
- News API (Google News, Bing News)
- Sentiment analysis AI (OpenAI GPT-4 or similar)
- Adverse media database (Dow Jones)

**Methods:**

```php
final class AdverseMediaScreeningService
{
    /**
     * Screen all entities for adverse media
     * 
     * @param int $kybVerificationId KYB verification ID
     * @param array $uboChain UBO chain
     * @param string $correlationId Correlation ID
     * @return array Adverse media results
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array;
    
    /**
     * Search for news about entity
     * 
     * @param string $entityName Entity name
     * @param int $daysBack Number of days to search
     * @return array News articles
     */
    public function searchNews(string $entityName, int $daysBack = 365): array;
    
    /**
     * Analyze sentiment of news article
     * 
     * @param string $articleTitle Article title
     * @param string $articleBody Article body
     * @return array Sentiment analysis result
     */
    public function analyzeSentiment(
        string $articleTitle,
        string $articleBody
    ): array;
}
```

**Data Structure:**

```php
[
    [
        'entity_name' => 'ООО "Пример"',
        'articles' => [
            [
                'title' => 'Компания обвинена в мошенничестве',
                'source' => 'РБК',
                'url' => 'https://...',
                'published_at' => '2026-03-15T10:00:00Z',
                'sentiment' => 'negative', // positive, neutral, negative
                'sentiment_score' => -0.8, // -1.0 to 1.0
                'relevance' => 'high', // low, medium, high
                'categories' => ['fraud', 'corruption'],
            ],
        ],
        'overall_sentiment' => 'negative',
        'adverse_media_count' => 5,
        'risk_level' => 'high',
        'requires_manual_review' => true,
        'screened_at' => '2026-04-19T10:00:00Z',
    ],
]
```

---

### 3.5 LinkAnalysisService

**Purpose:** Build ownership graph and detect suspicious patterns.

**Dependencies:**
- Graph database (Neo4j or ArangoDB) - optional, can use PostgreSQL
- Graph algorithms (connected components, centrality)

**Methods:**

```php
final class LinkAnalysisService
{
    /**
     * Build ownership graph from UBO chain
     * 
     * @param int $kybVerificationId KYB verification ID
     * @param array $uboChain UBO chain
     * @param string $correlationId Correlation ID
     * @return array Graph analysis results
     */
    public function buildOwnershipGraph(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array;
    
    /**
     * Detect circular ownership patterns
     * 
     * @param array $graph Ownership graph
     * @return array Circular ownership detection
     */
    public function detectCircularOwnership(array $graph): array;
    
    /**
     * Detect cross-tenant relationships
     * 
     * @param array $graph Ownership graph
     * @param int $tenantId Current tenant ID
     * @return array Cross-tenant relationships
     */
    public function detectCrossTenantRelationships(
        array $graph,
        int $tenantId
    ): array;
    
    /**
     * Calculate centrality scores for entities
     * 
     * @param array $graph Ownership graph
     * @return array Centrality scores
     */
    public function calculateCentrality(array $graph): array;
}
```

**Data Structure:**

```php
[
    'nodes' => [
        [
            'id' => 'company_1234567890',
            'type' => 'company',
            'name' => 'ООО "Пример"',
            'inn' => '1234567890',
        ],
        [
            'id' => 'individual_987654321098',
            'type' => 'individual',
            'name' => 'Иванов Иван Иванович',
            'inn' => '987654321098',
        ],
    ],
    'edges' => [
        [
            'source' => 'company_1234567890',
            'target' => 'individual_987654321098',
            'ownership_percentage' => 75.0,
            'relationship' => 'owned_by',
        ],
    ],
    'analysis' => [
        'circular_ownership_detected' => false,
        'circular_paths' => [],
        'cross_tenant_relationships' => [
            // Relationships with other tenants in CatVRF
        ],
        'centrality_scores' => [
            'company_1234567890' => 0.8,
            'individual_987654321098' => 0.9,
        ],
        'suspicious_patterns' => [
            // Detected suspicious patterns
        ],
    ],
]
```

---

## 4. Database Schema

### Existing Tables (No Changes Required)
- `kyb_verifications` - Already has fields for UBO chain, sanctions, PEP, adverse media

### New Tables (Optional - can use JSON fields in existing table)

```sql
-- Optional: Separate table for UBO entities
CREATE TABLE kyb_ubo_entities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NOT NULL,
    entity_type ENUM('company', 'individual') NOT NULL,
    inn VARCHAR(12),
    name VARCHAR(500) NOT NULL,
    ownership_percentage DECIMAL(5,2),
    is_ultimate_beneficial_owner BOOLEAN DEFAULT FALSE,
    nested_owners JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE
);

-- Optional: Separate table for sanctions screenings
CREATE TABLE kyb_sanctions_screenings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NOT NULL,
    entity_type VARCHAR(20) NOT NULL,
    entity_name VARCHAR(500) NOT NULL,
    entity_inn VARCHAR(12),
    screening_source VARCHAR(50) NOT NULL, -- rosfinmonitoring, ofac, eu, un
    screening_status ENUM('no_match', 'match', 'potential_match') NOT NULL,
    match_details JSON,
    screened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE
);

-- Optional: Separate table for PEP screenings
CREATE TABLE kyb_pep_screenings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kyb_verification_id BIGINT UNSIGNED NOT NULL,
    individual_name VARCHAR(500) NOT NULL,
    individual_inn VARCHAR(12),
    pep_status ENUM('pep', 'former_pep', 'no_pep') NOT NULL,
    pep_level VARCHAR(20),
    pep_role VARCHAR(500),
    pep_country VARCHAR(100),
    pep_period VARCHAR(50),
    risk_level VARCHAR(10),
    requires_enhanced_due_diligence BOOLEAN DEFAULT FALSE,
    screened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kyb_verification_id) REFERENCES kyb_verifications(id) ON DELETE CASCADE
);
```

---

## 5. Configuration

### Environment Variables

```env
# Kontur.Focus API
KONTUR_API_KEY=your_kontur_api_key
KONTUR_API_URL=https://focus-api.kontur.ru/api3

# Spark Interfax API (alternative)
SPARK_API_KEY=your_spark_api_key
SPARK_API_URL=https://api.spark-interfax.ru

# DaData API (for basic validation)
DADATA_API_KEY=your_dadata_api_key
DADATA_SECRET_KEY=your_dadata_secret

# World-Check API (PEP screening)
WORLDCHECK_API_KEY=your_worldcheck_api_key
WORLDCHECK_API_URL=https://api.world-check.com

# Dow Jones (Adverse Media)
DOWJONES_API_KEY=your_dowjones_api_key
DOWJONES_API_URL=https://api.dowjones.com

# OpenAI (Sentiment analysis)
OPENAI_API_KEY=your_openai_api_key

# Sanctions Lists (local files for offline fallback)
SANCTIONS_LISTS_PATH=/storage/sanctions/
```

### Config File

```php
// config/kyb.php
return [
    'kontur' => [
        'api_key' => env('KONTUR_API_KEY'),
        'api_url' => env('KONTUR_API_URL', 'https://focus-api.kontur.ru/api3'),
        'timeout' => 10,
        'rate_limit' => 100, // requests per minute
    ],
    
    'spark' => [
        'api_key' => env('SPARK_API_KEY'),
        'api_url' => env('SPARK_API_URL', 'https://api.spark-interfax.ru'),
        'timeout' => 10,
    ],
    
    'worldcheck' => [
        'api_key' => env('WORLDCHECK_API_KEY'),
        'api_url' => env('WORLDCHECK_API_URL', 'https://api.world-check.com'),
        'timeout' => 15,
    ],
    
    'dowjones' => [
        'api_key' => env('DOWJONES_API_KEY'),
        'api_url' => env('DOWJONES_API_URL', 'https://api.dowjones.com'),
        'timeout' => 15,
    ],
    
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => 'gpt-4-turbo',
        'timeout' => 30,
    ],
    
    'sanctions_lists' => [
        'path' => env('SANCTIONS_LISTS_PATH', storage_path('sanctions')),
        'auto_update' => true,
        'update_frequency' => 'daily',
    ],
    
    'caching' => [
        'enabled' => true,
        'ttl' => 86400, // 24 hours
    ],
];
```

---

## 6. Implementation Plan

### Week 1: UBOAnalysisService
- Implement UBOAnalysisService with Kontur.Focus integration
- Add DaData fallback for basic INN validation
- Implement shell company detection
- Write unit tests
- Update KYBService to use new service

### Week 2: SanctionsScreeningService
- Implement SanctionsScreeningService with Росфинмониторинг API
- Add OFAC, EU, UN sanctions list integration
- Implement fuzzy matching algorithm
- Write unit tests
- Update KYBService to use new service

### Week 3: PEPScreeningService + AdverseMediaScreeningService
- Implement PEPScreeningService with World-Check API
- Implement AdverseMediaScreeningService with news API + sentiment analysis
- Write unit tests for both services
- Update KYBService to use new services

### Week 4: LinkAnalysisService + Testing
- Implement LinkAnalysisService with graph analysis
- Add circular ownership detection
- Add cross-tenant relationship detection
- Write integration tests
- Update Filament dashboard to display new data
- End-to-end testing

---

## 7. Testing Strategy

### Unit Tests
- `UBOAnalysisServiceTest` - Test UBO extraction, shell company detection
- `SanctionsScreeningServiceTest` - Test sanctions matching, fuzzy matching
- `PEPScreeningServiceTest` - Test PEP classification
- `AdverseMediaScreeningServiceTest` - Test news search, sentiment analysis
- `LinkAnalysisServiceTest` - Test graph analysis, circular ownership

### Integration Tests
- `KYBServiceIntegrationTest` - Test full KYB workflow end-to-end
- Test with real Kontur.Focus API (sandbox environment)
- Test with real sanctions lists

### Contract Tests
- Test external API contracts (Kontur, World-Check, etc.)
- Mock external APIs for unit tests

---

## 8. Security Considerations

- **API Keys:** Store in environment variables, never commit to repo
- **Rate Limiting:** Implement rate limiting for external API calls
- **Caching:** Cache external API responses to reduce costs
- **Audit Logging:** Log all KYB verification actions to audit_logs table
- **Fraud Control:** Integrate with FraudControlService for all operations
- **Data Privacy:** Anonymize PII before sending to external AI services (OpenAI)

---

## 9. Performance Considerations

- **Async Processing:** Use Laravel Queues for heavy operations (news search, sentiment analysis)
- **Caching:** Redis cache for sanctions lists (update daily)
- **Batch Processing:** Screen multiple entities in parallel where possible
- **Timeouts:** Set appropriate timeouts for external API calls (10-30 seconds)
- **Fallback:** Implement fallback to local sanctions lists if APIs fail

---

## 10. Success Criteria

- [ ] All 5 services implemented and tested
- [ ] KYBService successfully completes verification workflow
- [ ] External API integrations working (Kontur, World-Check, etc.)
- [ ] Filament dashboard displays KYB verification results
- [ ] Unit test coverage > 80%
- [ ] Integration tests passing
- [ ] Fraud control integration working
- [ ] Audit logging working
- [ ] Performance: KYB verification completes < 30 seconds
- [ ] No runtime errors in KYBService

---

## 11. Rollback Plan

If critical issues arise:
1. Disable KYB verification via feature flag
2. Revert to manual verification only
3. Clear caches
4. Monitor logs
5. Hotfix within 4 hours

---

**Document Status:** Ready for Implementation  
**Next Steps:** Begin Week 1 implementation (UBOAnalysisService)
