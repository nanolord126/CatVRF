# KYB Enhancements Technical Specification
## PEP Screening, Adverse Media, AI Link Analysis

**Version:** 1.0  
**Date:** April 19, 2026  
**Priority:** 🔴 CRITICAL (Blocker for RF market launch)  
**Estimated Effort:** 3-4 weeks  
**Complexity:** High

---

## 1. Overview

This specification details the implementation of three critical KYB (Know Your Business) enhancements required for 115-ФЗ compliance in Russia and AMLD5/AMLD6 compliance in EU:

1. **PEP Screening Service** - Politically Exposed Persons detection
2. **Adverse Media Screening Service** - News monitoring for negative coverage
3. **AI Link Analysis Service** - Graph analysis of UBO chains

### Current State
- ✅ KYBService exists with basic UBO extraction (UBOAnalysisService)
- ✅ SanctionsScreeningService (Росфинмониторинг, OFAC, EU)
- ✅ PEPRecord model exists (no service implementation)
- ✅ AdverseMediaAlert model exists (no service implementation)
- ❌ No PEP screening service
- ❌ No adverse media screening service
- ❌ No AI link analysis

### Target State
- Full PEP screening with World-Check/Dow Jones integration
- Automated adverse media monitoring with AI sentiment analysis
- Graph-based UBO chain analysis for hidden ownership detection
- Enhanced manual review queue with risk-based prioritization

---

## 2. PEP Screening Service

### 2.1. Requirements

**Functional Requirements:**
1. Screen all entities in UBO chain against PEP databases
2. Classify PEPs by category (Head of State, Senior Official, etc.)
3. Identify family associates and close associates
4. Detect former PEPs with cooling-off period monitoring
5. Risk-based enhanced due diligence for PEP matches
6. Automated monthly PEP re-screening
7. Manual review workflow for potential matches
8. PEP status change notifications

**Non-Functional Requirements:**
- Screening latency: <500ms per entity
- API availability: 99.9%
- Data freshness: Daily updates from provider
- Audit trail for all screening decisions

### 2.2. External Providers

**Primary Options:**
1. **World-Check (Refinitiv)** - Industry standard, comprehensive coverage
   - API: World-Check One API
   - Coverage: 240+ countries, 4M+ PEP records
   - Cost: ~$5K/month

2. **Dow Jones Risk & Compliance** - Alternative provider
   - API: Dow Jones Risk API
   - Coverage: 200+ countries, 3M+ PEP records
   - Cost: ~$4K/month

3. **Russian Provider (Kontur.Focus)** - Local alternative
   - API: Kontur.Focus PEP endpoint
   - Coverage: RF-only, 500K+ records
   - Cost: ~$1K/month

**Recommendation:** World-Check (primary) + Kontur.Focus (fallback for RF-specific)

### 2.3. Service Architecture

```php
// app/Services/KYB/PEPScreeningService.php

final readonly class PEPScreeningService
{
    public function __construct(
        private readonly WorldCheckClient $worldCheck,
        private readonly KonturFocusClient $konturFocus,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Screen all entities in UBO chain for PEP status
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array {
        // Fraud check
        $this->fraudControl->check(
            userId: null,
            operationType: 'pep_screening',
            amount: 0,
            correlationId: $correlationId,
        );

        $results = [];
        
        foreach ($uboChain as $entity) {
            $results[] = $this->screenEntity(
                $kybVerificationId,
                $entity,
                $correlationId
            );
        }

        return $results;
    }

    /**
     * Screen single entity for PEP status
     */
    public function screenEntity(
        int $kybVerificationId,
        array $entity,
        string $correlationId = ''
    ): array {
        // Try World-Check first
        $worldCheckResult = $this->screenWorldCheck($entity);
        
        // Fallback to Kontur if needed
        if ($worldCheckResult['status'] === 'error') {
            $worldCheckResult = $this->screenKontur($entity);
        }

        // Store result
        $pepRecord = PEPRecord::create([
            'kyb_verification_id' => $kybVerificationId,
            'ubo_chain_id' => $entity['id'] ?? null,
            'screening_type' => 'entity',
            'screening_provider' => $worldCheckResult['provider'],
            'entity_name' => $entity['entity_name'],
            'entity_inn' => $entity['entity_inn'] ?? null,
            'entity_dob' => $entity['entity_dob'] ?? null,
            'entity_nationality' => $entity['entity_nationality'] ?? null,
            'pep_status' => $worldCheckResult['pep_status'],
            'pep_position' => $worldCheckResult['position'] ?? null,
            'pep_country' => $worldCheckResult['country'] ?? null,
            'pep_start_date' => $worldCheckResult['start_date'] ?? null,
            'pep_end_date' => $worldCheckResult['end_date'] ?? null,
            'pep_category' => $worldCheckResult['category'] ?? null,
            'screening_details' => $worldCheckResult,
            'screened_at' => now(),
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->logEvent('pep_screening_completed', [
            'kyb_verification_id' => $kybVerificationId,
            'entity_name' => $entity['entity_name'],
            'pep_status' => $worldCheckResult['pep_status'],
            'provider' => $worldCheckResult['provider'],
        ], 'security');

        return [
            'entity_name' => $entity['entity_name'],
            'pep_status' => $worldCheckResult['pep_status'],
            'pep_record_id' => $pepRecord->id,
        ];
    }

    /**
     * Re-screen existing PEP records (monthly)
     */
    public function reScreenPEPs(string $correlationId = ''): array
    {
        $activePEPs = PEPRecord::where('pep_status', 'pep')
            ->where('screened_at', '<=', now()->subMonth())
            ->get();

        $results = [];
        
        foreach ($activePEPs as $pep) {
            $results[] = $this->screenEntity(
                $pep->kyb_verification_id,
                [
                    'entity_name' => $pep->entity_name,
                    'entity_inn' => $pep->entity_inn,
                    'entity_dob' => $pep->entity_dob?->format('Y-m-d'),
                    'entity_nationality' => $pep->entity_nationality,
                ],
                $correlationId
            );
        }

        return $results;
    }

    private function screenWorldCheck(array $entity): array
    {
        // Implementation using World-Check One API
        try {
            $response = $this->worldCheck->search([
                'name' => $entity['entity_name'],
                'date_of_birth' => $entity['entity_dob'] ?? null,
                'identification_number' => $entity['entity_inn'] ?? null,
            ]);

            if (empty($response['matches'])) {
                return [
                    'status' => 'clean',
                    'pep_status' => 'not_pep',
                    'provider' => 'world_check',
                ];
            }

            $match = $response['matches'][0];
            
            return [
                'status' => 'match',
                'pep_status' => $this->determinePEPStatus($match),
                'position' => $match['position'] ?? null,
                'country' => $match['country'] ?? null,
                'start_date' => $match['start_date'] ?? null,
                'end_date' => $match['end_date'] ?? null,
                'category' => $match['category'] ?? null,
                'provider' => 'world_check',
                'raw_match' => $match,
            ];
        } catch (\Throwable $e) {
            Log::error('World-Check PEP screening failed', [
                'entity_name' => $entity['entity_name'],
                'error' => $e->getMessage(),
            ]);
            
            return ['status' => 'error', 'pep_status' => 'unknown', 'provider' => 'world_check'];
        }
    }

    private function determinePEPStatus(array $match): string
    {
        if (isset($match['end_date']) && !empty($match['end_date'])) {
            // Check if within cooling-off period (usually 12-24 months)
            $endDate = Carbon::parse($match['end_date']);
            $coolingOffMonths = 18; // Configurable
            
            if ($endDate->addMonths($coolingOffMonths)->isFuture()) {
                return 'former_pep';
            }
            
            return 'not_pep';
        }

        return 'pep';
    }
}
```

### 2.4. Database Schema

```php
// database/migrations/2026_04_19_000003_enhance_pep_records_table.php

public function up(): void
{
    Schema::table('pep_records', function (Blueprint $table) {
        $table->index(['kyb_verification_id', 'pep_status']);
        $table->index(['entity_inn', 'pep_status']);
        $table->index('screened_at');
        $table->index(['pep_status', 'pep_category']);
        
        // Add columns for enhanced tracking
        $table->boolean('requires_enhanced_due_diligence')->default(false);
        $table->timestamp('edd_completed_at')->nullable();
        $table->integer('edd_completed_by')->nullable();
        $table->text('edd_notes')->nullable();
        
        // Family/associate tracking
        $table->json('family_associates')->nullable();
        $table->json('close_associates')->nullable();
        
        // Risk scoring
        $table->integer('pep_risk_score')->default(0); // 0-100
        $table->string('pep_risk_level')->default('low'); // low, medium, high, critical
    });
}
```

### 2.5. Configuration

```php
// config/kyb.php

return [
    // ... existing config ...
    
    'pep_screening' => [
        'world_check' => [
            'api_key' => env('WORLDCHECK_API_KEY'),
            'api_url' => env('WORLDCHECK_API_URL', 'https://api-world-check.refinitiv.com'),
            'timeout' => 30,
        ],
        'kontur_focus' => [
            'api_key' => env('KONTUR_FOCUS_API_KEY'),
            'api_url' => env('KONTUR_FOCUS_API_URL', 'https://focus-api.kontur.ru'),
            'timeout' => 30,
        ],
        'rescreen_interval_days' => 30,
        'cooling_off_period_months' => 18,
        'enhanced_due_diligence_threshold' => 'high', // Risk level requiring EDD
    ],
];
```

---

## 3. Adverse Media Screening Service

### 3.1. Requirements

**Functional Requirements:**
1. Monitor news sources for negative coverage of entities
2. AI-powered sentiment analysis (positive, neutral, negative)
3. Relevance scoring for entity matching
4. Real-time alerts for critical adverse media
5. Verification workflow for potential matches
6. Historical adverse media tracking
7. Multi-language support (Russian, English)
8. Source credibility scoring

**Non-Functional Requirements:**
- Monitoring latency: <1 hour for critical news
- Sentiment accuracy: >85% (F1 score)
- API availability: 99.5%
- Audit trail for all alerts

### 3.2. External Providers

**Primary Options:**
1. **Google News API** - Broad coverage, good for Russian sources
2. **Bing News API** - Alternative with different source coverage
3. **LexisNexis** - Premium news aggregation (expensive)
4. **Russian Providers:** Яндекс.Новости, Лента.ру APIs

**Recommendation:** Google News API (primary) + Яндекс.Новости (RF-specific)

### 3.3. Service Architecture

```php
// app/Services/KYB/AdverseMediaScreeningService.php

final readonly class AdverseMediaScreeningService
{
    public function __construct(
        private readonly GoogleNewsClient $googleNews,
        private readonly YandexNewsClient $yandexNews,
        private readonly AISentimentService $sentimentService,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Screen entity for adverse media
     */
    public function screenEntity(
        int $kybVerificationId,
        array $entity,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: null,
            operationType: 'adverse_media_screening',
            amount: 0,
            correlationId: $correlationId,
        );

        // Search Google News
        $googleResults = $this->searchGoogleNews($entity['entity_name']);
        
        // Search Yandex News (for Russian sources)
        $yandexResults = $this->searchYandexNews($entity['entity_name']);
        
        // Combine and deduplicate
        $allArticles = array_merge($googleResults, $yandexResults);
        $uniqueArticles = $this->deduplicateArticles($allArticles);
        
        // Analyze sentiment for each article
        $alerts = [];
        foreach ($uniqueArticles as $article) {
            $sentiment = $this->sentimentService->analyze($article['title'] . ' ' . $article['summary']);
            
            if ($sentiment['sentiment'] === 'negative' && $sentiment['confidence'] > 0.7) {
                $alert = $this->createAdverseMediaAlert(
                    $kybVerificationId,
                    $entity,
                    $article,
                    $sentiment,
                    $correlationId
                );
                $alerts[] = $alert;
            }
        }

        return [
            'entity_name' => $entity['entity_name'],
            'articles_scanned' => count($uniqueArticles),
            'alerts_generated' => count($alerts),
            'alerts' => $alerts,
        ];
    }

    /**
     * Continuous monitoring for existing entities
     */
    public function monitorEntity(
        int $kybVerificationId,
        string $entityName,
        string $correlationId = ''
    ): void {
        // This would be called by a scheduled job (e.g., daily)
        $entity = ['entity_name' => $entityName];
        $this->screenEntity($kybVerificationId, $entity, $correlationId);
    }

    private function searchGoogleNews(string $entityName): array
    {
        try {
            $response = $this->googleNews->search([
                'q' => $entityName,
                'language' => 'ru,en',
                'sortBy' => 'publishedAt',
                'pageSize' => 50,
            ]);

            return $response['articles'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Google News search failed', [
                'entity_name' => $entityName,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function searchYandexNews(string $entityName): array
    {
        try {
            $response = $this->yandexNews->search([
                'query' => $entityName,
                'lang' => 'ru',
                'limit' => 50,
            ]);

            return $response['articles'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Yandex News search failed', [
                'entity_name' => $entityName,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function deduplicateArticles(array $articles): array
    {
        $seen = [];
        $unique = [];
        
        foreach ($articles as $article) {
            $key = md5($article['title'] . $article['url']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $article;
            }
        }
        
        return $unique;
    }

    private function createAdverseMediaAlert(
        int $kybVerificationId,
        array $entity,
        array $article,
        array $sentiment,
        string $correlationId
    ): array {
        $alert = AdverseMediaAlert::create([
            'kyb_verification_id' => $kybVerificationId,
            'ubo_chain_id' => $entity['id'] ?? null,
            'entity_name' => $entity['entity_name'],
            'entity_inn' => $entity['entity_inn'] ?? null,
            'alert_type' => $this->determineAlertType($article),
            'severity' => $this->determineSeverity($sentiment),
            'source_name' => $article['source']['name'] ?? 'Unknown',
            'source_url' => $article['url'],
            'publication_date' => $article['publishedAt'] ?? now(),
            'title' => $article['title'],
            'summary' => $article['description'] ?? $article['summary'] ?? '',
            'full_content' => $article['content'] ?? null,
            'relevance_score' => $sentiment['confidence'],
            'is_verified' => false,
            'correlation_id' => $correlationId,
        ]);

        // Auto-notify for critical alerts
        if ($alert->severity === 'critical') {
            $this->notifyCriticalAlert($alert);
        }

        // Audit log
        $this->audit->logEvent('adverse_media_alert_created', [
            'kyb_verification_id' => $kybVerificationId,
            'entity_name' => $entity['entity_name'],
            'severity' => $alert->severity,
            'source' => $alert->source_name,
        ], 'security');

        return $alert->toArray();
    }

    private function determineAlertType(array $article): string
    {
        $title = strtolower($article['title'] ?? '');
        
        if (str_contains($title, 'fraud') || str_contains($title, 'мошенничество')) {
            return 'fraud';
        }
        if (str_contains($title, 'corruption') || str_contains($title, 'коррупция')) {
            return 'corruption';
        }
        if (str_contains($title, 'money laundering') || str_contains($title, 'отмывание')) {
            return 'money_laundering';
        }
        if (str_contains($title, 'sanction') || str_contains($title, 'санкци')) {
            return 'sanctions';
        }
        
        return 'negative_coverage';
    }

    private function determineSeverity(array $sentiment): string
    {
        $confidence = $sentiment['confidence'];
        
        if ($confidence >= 0.9) {
            return 'critical';
        }
        if ($confidence >= 0.8) {
            return 'high';
        }
        if ($confidence >= 0.7) {
            return 'medium';
        }
        
        return 'low';
    }

    private function notifyCriticalAlert(AdverseMediaAlert $alert): void
    {
        // Send notification to compliance team
        // Could be Slack, Telegram, email, etc.
        Log::critical('Critical adverse media alert', [
            'alert_id' => $alert->id,
            'entity_name' => $alert->entity_name,
            'title' => $alert->title,
            'source' => $alert->source_name,
        ]);
    }
}
```

### 3.4. AI Sentiment Service

```php
// app/Services/AI/AISentimentService.php

final readonly class AISentimentService
{
    public function __construct(
        private readonly OpenAIClient $openai,
    ) {}

    public function analyze(string $text): array
    {
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Analyze the sentiment of the following text. Return JSON with: sentiment (positive/neutral/negative), confidence (0-1), and key_topics (array of strings).'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $result = json_decode($response->choices[0]->message->content, true);
            
            return [
                'sentiment' => $result['sentiment'] ?? 'neutral',
                'confidence' => $result['confidence'] ?? 0.5,
                'key_topics' => $result['key_topics'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('AI sentiment analysis failed', [
                'text' => substr($text, 0, 200),
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to rule-based sentiment
            return $this->ruleBasedSentiment($text);
        }
    }

    private function ruleBasedSentiment(string $text): array
    {
        $negativeWords = ['fraud', 'corruption', 'scandal', 'мошенничество', 'коррупция', 'скандал'];
        $textLower = strtolower($text);
        
        $matchCount = 0;
        foreach ($negativeWords as $word) {
            if (str_contains($textLower, $word)) {
                $matchCount++;
            }
        }
        
        if ($matchCount >= 2) {
            return ['sentiment' => 'negative', 'confidence' => 0.7, 'key_topics' => []];
        }
        if ($matchCount === 1) {
            return ['sentiment' => 'negative', 'confidence' => 0.5, 'key_topics' => []];
        }
        
        return ['sentiment' => 'neutral', 'confidence' => 0.5, 'key_topics' => []];
    }
}
```

### 3.5. Database Schema

```php
// database/migrations/2026_04_19_000004_enhance_adverse_media_alerts_table.php

public function up(): void
{
    Schema::table('adverse_media_alerts', function (Blueprint $table) {
        $table->index(['kyb_verification_id', 'severity']);
        $table->index(['entity_inn', 'severity']);
        $table->index('publication_date');
        $table->index(['is_verified', 'severity']);
        
        // Add columns for enhanced tracking
        $table->json('key_topics')->nullable();
        $table->string('language', 10)->default('ru');
        $table->integer('source_credibility_score')->default(50); // 0-100
        $table->boolean('is_false_positive')->default(false);
        $table->integer('false_positive_verified_by')->nullable();
        $table->timestamp('false_positive_verified_at')->nullable();
        $table->text('false_positive_notes')->nullable();
        
        // Monitoring scheduling
        $table->boolean('enable_monitoring')->default(false);
        $table->timestamp('last_monitored_at')->nullable();
        $table->timestamp('next_monitor_at')->nullable();
    });
}
```

---

## 4. AI Link Analysis Service

### 4.1. Requirements

**Functional Requirements:**
1. Build graph of ownership relationships from UBO chains
2. Detect hidden ownership structures (shell companies)
3. Identify circular ownership patterns
4. Cross-tenant relationship mapping
5. Money laundering pattern detection
6. Beneficiary clustering
7. Risk scoring based on network analysis

**Non-Functional Requirements:**
- Graph processing time: <2s for 1000 entities
- Memory usage: <500MB for typical tenant graph
- API availability: 99.5%

### 4.2. Technology Stack

**Graph Database Options:**
1. **Neo4j** - Industry standard, Cypher query language
2. **ArangoDB** - Multi-model, good for mixed workloads
3. **RedisGraph** - Lightweight, built on Redis
4. **PostgreSQL with pgRouting** - No additional infrastructure

**Recommendation:** Neo4j (primary) for complex graph queries, with PostgreSQL fallback for simple relationships

### 4.3. Service Architecture

```php
// app/Services/KYB/AILinkAnalysisService.php

final readonly class AILinkAnalysisService
{
    public function __construct(
        private readonly Neo4jClient $neo4j,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Build ownership graph from UBO chain
     */
    public function buildOwnershipGraph(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: null,
            operationType: 'link_analysis',
            amount: 0,
            correlationId: $correlationId,
        );

        // Clear existing graph for this verification
        $this->clearGraph($kybVerificationId);

        // Build graph nodes and relationships
        $nodes = [];
        $relationships = [];
        
        foreach ($uboChain as $entity) {
            $nodes[] = $this->createNode($entity);
            
            // Create ownership relationships
            if (isset($entity['parent_id'])) {
                $relationships[] = $this->createRelationship(
                    $entity['parent_id'],
                    $entity['id'],
                    'OWNS',
                    ['percentage' => $entity['ownership_percentage']]
                );
            }
        }

        // Insert into Neo4j
        $this->insertGraph($kybVerificationId, $nodes, $relationships);

        // Analyze graph
        $analysis = $this->analyzeGraph($kybVerificationId);

        // Store analysis results
        $this->storeAnalysisResults($kybVerificationId, $analysis, $correlationId);

        return $analysis;
    }

    /**
     * Detect circular ownership patterns
     */
    public function detectCircularOwnership(int $kybVerificationId): array
    {
        $query = '
            MATCH path = (a:Entity)-[:OWNS*]->(a)
            WHERE a.kyb_verification_id = $kybVerificationId
            RETURN path, length(path) as cycle_length
            ORDER BY cycle_length ASC
            LIMIT 10
        ';

        $result = $this->neo4j->run($query, [
            'kybVerificationId' => $kybVerificationId,
        ]);

        return $result->toArray();
    }

    /**
     * Detect shell companies (entities with no real operations)
     */
    public function detectShellCompanies(int $kybVerificationId): array
    {
        $query = '
            MATCH (e:Entity)
            WHERE e.kyb_verification_id = $kybVerificationId
            WHERE e.entity_type = "company"
            WHERE NOT EXISTS((e)-[:OPERATES_IN]->())
            WHERE size((e)<-[:OWNS]-()) > 0
            RETURN e
        ';

        $result = $this->neo4j->run($query, [
            'kybVerificationId' => $kybVerificationId,
        ]);

        return $result->toArray();
    }

    /**
     * Cross-tenant relationship mapping
     */
    public function findCrossTenantRelationships(string $entityInn): array
    {
        $query = '
            MATCH (e1:Entity {entity_inn: $entityInn})
            MATCH (e1)-[:OWNS*]-(e2:Entity)
            WHERE e1.kyb_verification_id <> e2.kyb_verification_id
            RETURN DISTINCT e2.kyb_verification_id, e2.entity_name, e2.entity_inn
            LIMIT 50
        ';

        $result = $this->neo4j->run($query, [
            'entityInn' => $entityInn,
        ]);

        return $result->toArray();
    }

    /**
     * Money laundering pattern detection
     */
    public function detectMoneyLaunderingPatterns(int $kybVerificationId): array
    {
        $patterns = [];

        // Pattern 1: Rapid ownership transfers
        $query1 = '
            MATCH (e1:Entity)-[r:OWNS]->(e2:Entity)
            WHERE e1.kyb_verification_id = $kybVerificationId
            WHERE r.transfer_date >= date() - duration('P30D')
            RETURN count(r) as rapid_transfers
        ';
        $rapidTransfers = $this->neo4j->run($query1, [
            'kybVerificationId' => $kybVerificationId,
        ])->first();

        if ($rapidTransfers['rapid_transfers'] > 3) {
            $patterns[] = [
                'type' => 'rapid_ownership_transfers',
                'severity' => 'high',
                'count' => $rapidTransfers['rapid_transfers'],
            ];
        }

        // Pattern 2: Complex multi-layer structures
        $query2 = '
            MATCH path = (e:Entity)-[:OWNS*]->(end:Entity)
            WHERE e.kyb_verification_id = $kybVerificationId
            WITH length(path) as depth
            RETURN max(depth) as max_depth
        ';
        $maxDepth = $this->neo4j->run($query2, [
            'kybVerificationId' => $kybVerificationId,
        ])->first();

        if ($maxDepth['max_depth'] > 4) {
            $patterns[] = [
                'type' => 'complex_structure',
                'severity' => 'medium',
                'max_depth' => $maxDepth['max_depth'],
            ];
        }

        return $patterns;
    }

    /**
     * Comprehensive graph analysis
     */
    private function analyzeGraph(int $kybVerificationId): array
    {
        return [
            'circular_ownership' => $this->detectCircularOwnership($kybVerificationId),
            'shell_companies' => $this->detectShellCompanies($kybVerificationId),
            'money_laundering_patterns' => $this->detectMoneyLaunderingPatterns($kybVerificationId),
            'beneficiary_clusters' => $this->findBeneficiaryClusters($kybVerificationId),
            'network_risk_score' => $this->calculateNetworkRiskScore($kybVerificationId),
        ];
    }

    private function calculateNetworkRiskScore(int $kybVerificationId): array
    {
        $circular = count($this->detectCircularOwnership($kybVerificationId));
        $shells = count($this->detectShellCompanies($kybVerificationId));
        $mlPatterns = count($this->detectMoneyLaunderingPatterns($kybVerificationId));

        $score = ($circular * 30) + ($shells * 20) + ($mlPatterns * 25);
        $score = min(100, $score);

        $level = match (true) {
            $score >= 70 => 'critical',
            $score >= 50 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'factors' => [
                'circular_ownership' => $circular,
                'shell_companies' => $shells,
                'ml_patterns' => $mlPatterns,
            ],
        ];
    }

    private function createNode(array $entity): array
    {
        return [
            'id' => $entity['id'],
            'kyb_verification_id' => $entity['kyb_verification_id'],
            'entity_type' => $entity['entity_type'],
            'entity_name' => $entity['entity_name'],
            'entity_inn' => $entity['entity_inn'] ?? null,
            'ownership_percentage' => $entity['ownership_percentage'] ?? 0,
            'is_ultimate_beneficial_owner' => $entity['is_ultimate_beneficial_owner'] ?? false,
        ];
    }

    private function createRelationship(string $from, string $to, string $type, array $properties = []): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'properties' => $properties,
        ];
    }

    private function insertGraph(int $kybVerificationId, array $nodes, array $relationships): void
    {
        // Implementation to insert nodes and relationships into Neo4j
        // This would use Neo4j's bulk insert for performance
    }

    private function clearGraph(int $kybVerificationId): void
    {
        $query = '
            MATCH (e:Entity {kyb_verification_id: $kybVerificationId})
            DETACH DELETE e
        ';

        $this->neo4j->run($query, [
            'kybVerificationId' => $kybVerificationId,
        ]);
    }

    private function storeAnalysisResults(int $kybVerificationId, array $analysis, string $correlationId): void
    {
        // Store analysis results in KYBVerification or separate table
        KYBVerification::where('id', $kybVerificationId)->update([
            'link_analysis' => $analysis,
        ]);

        $this->audit->logEvent('link_analysis_completed', [
            'kyb_verification_id' => $kybVerificationId,
            'network_risk_score' => $analysis['network_risk_score']['score'],
            'network_risk_level' => $analysis['network_risk_score']['level'],
        ], 'security');
    }

    private function findBeneficiaryClusters(int $kybVerificationId): array
    {
        // Implementation to find clusters of related beneficiaries
        return [];
    }
}
```

### 4.4. Database Schema

```php
// database/migrations/2026_04_19_000005_create_link_analysis_results_table.php

public function up(): void
{
    Schema::create('link_analysis_results', function (Blueprint $table) {
        $table->id();
        $table->foreignId('kyb_verification_id')->constrained()->onDelete('cascade');
        $table->json('circular_ownership')->nullable();
        $table->json('shell_companies')->nullable();
        $table->json('money_laundering_patterns')->nullable();
        $table->json('beneficiary_clusters')->nullable();
        $table->integer('network_risk_score')->default(0);
        $table->string('network_risk_level')->default('low');
        $table->json('network_risk_factors')->nullable();
        $table->timestamp('analyzed_at')->nullable();
        $table->string('correlation_id')->nullable();
        $table->timestamps();
        
        $table->index(['kyb_verification_id', 'network_risk_level']);
        $table->index('network_risk_score');
    });
}
```

---

## 5. Enhanced Manual Review Queue

### 5.1. Filament Dashboard Enhancement

```php
// app/Filament/Resources/KYBVerificationResource.php

class KYBVerificationResource extends Resource
{
    protected static ?string $model = KYBVerification::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationGroup = 'Compliance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('inn')
                            ->label('INN')
                            ->required(),
                        Forms\Components\Select::make('verification_status')
                            ->options([
                                'in_progress' => 'In Progress',
                                'requires_review' => 'Requires Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
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
                    ]),
                
                Forms\Components\Section::make('Risk Factors')
                    ->schema([
                        Forms\Components\KeyValue::make('ubo_chain')
                            ->label('UBO Chain')
                            ->keyLabel('Entity')
                            ->valueLabel('Ownership %'),
                        Forms\Components\KeyValue::make('sanctions_screening')
                            ->label('Sanctions Screening'),
                        Forms\Components\KeyValue::make('pep_screening')
                            ->label('PEP Screening'),
                        Forms\Components\KeyValue::make('adverse_media')
                            ->label('Adverse Media'),
                        Forms\Components\KeyValue::make('link_analysis')
                            ->label('Link Analysis'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Manual Review')
                    ->schema([
                        Forms\Components\Textarea::make('manual_review_notes')
                            ->label('Review Notes')
                            ->rows(3),
                        Forms\Components\Select::make('manual_review_assigned_to')
                            ->relationship('assignedTo', 'name')
                            ->label('Assigned To'),
                    ])->visible(fn ($record) => $record?->manual_review_required),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inn')
                    ->label('INN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('verification_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'requires_review' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('risk_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->sortable(),
                Tables\Columns\IconColumn::make('manual_review_required')
                    ->boolean()
                    ->label('Needs Review'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'in_progress' => 'In Progress',
                        'requires_review' => 'Requires Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('risk_level')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\TernaryFilter::make('manual_review_required')
                    ->label('Needs Review'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (KYBVerification $record) {
                        $record->update([
                            'verification_status' => 'approved',
                            'manual_review_required' => false,
                            'manual_reviewed_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (KYBVerification $record, array $data) {
                        $record->update([
                            'verification_status' => 'rejected',
                            'manual_review_required' => false,
                            'manual_reviewed_at' => now(),
                            'manual_review_notes' => $data['rejection_reason'],
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_bulk')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function (KYBVerification $record) {
                            $record->update([
                                'verification_status' => 'approved',
                                'manual_review_required' => false,
                                'manual_reviewed_at' => now(),
                            ]);
                        });
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
```

---

## 6. Scheduled Jobs

```php
// app/Jobs/PEPRescreeningJob.php

final class PEPRescreeningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $correlationId = ''
    ) {}

    public function handle(PEPScreeningService $pepScreening): void
    {
        $pepScreening->reScreenPEPs($this->correlationId);
    }
}

// app/Jobs/AdverseMediaMonitoringJob.php

final class AdverseMediaMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $correlationId = ''
    ) {}

    public function handle(AdverseMediaScreeningService $adverseMedia): void
    {
        // Monitor all active businesses for adverse media
        $activeVerifications = KYBVerification::where('verification_status', 'approved')
            ->where('expires_at', '>', now())
            ->get();

        foreach ($activeVerifications as $verification) {
            foreach ($verification->uboChain as $entity) {
                $adverseMedia->monitorEntity(
                    $verification->id,
                    $entity->entity_name,
                    $this->correlationId
                );
            }
        }
    }
}

// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // PEP re-screening - daily at 02:00 UTC
    $schedule->job(new PEPRescreeningJob())
        ->dailyAt('02:00')
        ->timezone('UTC');

    // Adverse media monitoring - every 6 hours
    $schedule->job(new AdverseMediaMonitoringJob())
        ->everySixHours();
}
```

---

## 7. Testing Strategy

```php
// tests/Unit/Services/KYB/PEPScreeningServiceTest.php

final class PEPScreeningServiceTest extends TestCase
{
    public function test_screen_entity_returns_not_pep_for_clean_entity(): void
    {
        $service = app(PEPScreeningService::class);
        
        $result = $service->screenEntity(
            kybVerificationId: 1,
            entity: [
                'entity_name' => 'Clean Business LLC',
                'entity_inn' => '1234567890',
            ],
            correlationId: 'test-123',
        );

        $this->assertEquals('not_pep', $result['pep_status']);
    }

    public function test_screen_entity_detects_pep(): void
    {
        // Mock World-Check to return PEP match
        // ... implementation
    }

    public function test_rescreen_peps_updates_status(): void
    {
        // Test that re-screening updates PEP status
        // ... implementation
    }
}

// tests/Unit/Services/KYB/AdverseMediaScreeningServiceTest.php

final class AdverseMediaScreeningServiceTest extends TestCase
{
    public function test_screen_entity_detects_negative_sentiment(): void
    {
        // Mock news API and sentiment service
        // ... implementation
    }

    public function test_monitor_entity_creates_alert(): void
    {
        // Test continuous monitoring
        // ... implementation
    }
}

// tests/Unit/Services/KYB/AILinkAnalysisServiceTest.php

final class AILinkAnalysisServiceTest extends TestCase
{
    public function test_detect_circular_ownership(): void
    {
        // Test circular ownership detection
        // ... implementation
    }

    public function test_detect_shell_companies(): void
    {
        // Test shell company detection
        // ... implementation
    }

    public function test_calculate_network_risk_score(): void
    {
        // Test risk score calculation
        // ... implementation
    }
}
```

---

## 8. Implementation Timeline

**Week 1: PEP Screening Service**
- Day 1-2: World-Check API integration
- Day 3-4: PEP Screening Service implementation
- Day 5: Unit tests + documentation

**Week 2: Adverse Media Screening Service**
- Day 1-2: Google/Yandex News API integration
- Day 3: AI Sentiment Service implementation
- Day 4: Adverse Media Screening Service implementation
- Day 5: Unit tests + documentation

**Week 3: AI Link Analysis Service**
- Day 1-2: Neo4j setup and integration
- Day 3-4: AI Link Analysis Service implementation
- Day 5: Unit tests + documentation

**Week 4: Integration + Testing**
- Day 1-2: Integration with existing KYBService
- Day 3: Enhanced manual review queue
- Day 4: Scheduled jobs setup
- Day 5: End-to-end testing + documentation

---

## 9. Configuration Checklist

- [ ] World-Check API credentials
- [ ] Kontur.Focus API credentials
- [ ] Google News API credentials
- [ ] Yandex News API credentials
- [ ] OpenAI API credentials (for sentiment analysis)
- [ ] Neo4j database setup
- [ ] Environment variables configuration
- [ ] Scheduled jobs setup
- [ ] Monitoring and alerting configuration

---

## 10. Success Criteria

- [ ] PEP screening accuracy >95%
- [ ] Adverse media detection latency <1 hour
- [ ] Link analysis processing time <2s for 1000 entities
- [ ] All unit tests passing (>90% coverage)
- [ ] Manual review queue operational
- [ ] Scheduled jobs running successfully
- [ ] Integration with existing KYBService verified
- [ ] Documentation complete

---

**Document Version:** 1.0  
**Last Updated:** April 19, 2026  
**Next Review:** April 26, 2026
