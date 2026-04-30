<?php

declare(strict_types=1);

namespace App\Services\KYB;

use App\Models\KYBVerification;
use App\Models\LinkAnalysisResult;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;

final readonly class AILinkAnalysisService
{
    use WithAuditLogging;

    private const MAX_DEPTH = 5;

    private const UBO_THRESHOLD = 25; // 25% ownership

    private const CRITICAL_RISK_THRESHOLD = 70;

    private const HIGH_RISK_THRESHOLD = 50;

    private const MEDIUM_RISK_THRESHOLD = 30;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
    ) {}

    /**
     * Build ownership graph from UBO chain
     */
    public function buildOwnershipGraph(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array {
        if (empty($uboChain)) {
            return [
                'circular_ownership' => [],
                'shell_companies' => [],
                'money_laundering_patterns' => [],
                'beneficiary_clusters' => [],
                'network_risk_score' => 0,
                'network_risk_level' => 'low',
                'graph_depth' => 0,
                'node_count' => 0,
            ];
        }
        $this->fraudControl->check(
            userId: null,
            operationType: 'link_analysis',
            amount: 0,
            correlationId: $correlationId,
        );

        // Store UBO chain in memory for analysis
        $graph = $this->buildGraphFromChain($uboChain);

        // Analyze graph
        $analysis = $this->analyzeGraph($graph, $kybVerificationId);

        // Store analysis results
        $this->storeAnalysisResults($kybVerificationId, $analysis, $correlationId);

        return $analysis;
    }

    /**
     * Detect circular ownership patterns
     */
    public function detectCircularOwnership(int $kybVerificationId): array
    {
        $uboChain = $this->getUBOChain($kybVerificationId);
        $graph = $this->buildGraphFromChain($uboChain);

        return $this->findCycles($graph);
    }

    /**
     * Detect shell companies (entities with no real operations)
     */
    public function detectShellCompanies(int $kybVerificationId): array
    {
        $uboChain = $this->getUBOChain($kybVerificationId);

        return array_filter($uboChain, function ($entity) {
            // Shell company indicators:
            // 1. No employees
            // 2. No revenue
            // 3. No physical address
            // 4. Multiple ownership layers
            return $this->isShellCompany($entity);
        });
    }

    /**
     * Cross-tenant relationship mapping
     */
    public function findCrossTenantRelationships(string $entityInn): array
    {
        // Find all entities with same INN across different tenants
        return $this->db->table('ubo_chains')
            ->where('entity_inn', $entityInn)
            ->select('kyb_verification_id', 'entity_name', 'entity_inn')
            ->distinct()
            ->get()
            ->toArray();
    }

    /**
     * Money laundering pattern detection
     */
    public function detectMoneyLaunderingPatterns(int $kybVerificationId): array
    {
        $uboChain = $this->getUBOChain($kybVerificationId);
        $graph = $this->buildGraphFromChain($uboChain);

        $patterns = [];

        // Pattern 1: Rapid ownership transfers
        $rapidTransfers = $this->detectRapidTransfers($uboChain);
        if (count($rapidTransfers) > 3) {
            $patterns[] = [
                'type' => 'rapid_ownership_transfers',
                'severity' => 'high',
                'count' => count($rapidTransfers),
            ];
        }

        // Pattern 2: Complex multi-layer structures
        $maxDepth = $this->calculateMaxDepth($graph);
        if ($maxDepth > 4) {
            $patterns[] = [
                'type' => 'complex_structure',
                'severity' => 'medium',
                'max_depth' => $maxDepth,
            ];
        }

        // Pattern 3: Circular ownership
        $cycles = $this->findCycles($graph);
        if (count($cycles) > 0) {
            $patterns[] = [
                'type' => 'circular_ownership',
                'severity' => 'high',
                'cycle_count' => count($cycles),
            ];
        }

        // Pattern 4: Many shell companies
        $shellCompanies = $this->detectShellCompanies($kybVerificationId);
        if (count($shellCompanies) > 2) {
            $patterns[] = [
                'type' => 'shell_company_cluster',
                'severity' => 'high',
                'count' => count($shellCompanies),
            ];
        }

        return $patterns;
    }

    /**
     * Comprehensive graph analysis
     */
    private function analyzeGraph(array $graph, int $kybVerificationId): array
    {
        return [
            'circular_ownership' => $this->findCycles($graph),
            'shell_companies' => $this->detectShellCompanies($kybVerificationId),
            'money_laundering_patterns' => $this->detectMoneyLaunderingPatterns($kybVerificationId),
            'beneficiary_clusters' => $this->findBeneficiaryClusters($graph),
            'network_risk_score' => $this->calculateNetworkRiskScore($graph, $kybVerificationId),
            'graph_depth' => $this->calculateMaxDepth($graph),
            'node_count' => count($graph),
        ];
    }

    private function calculateNetworkRiskScore(array $graph, int $kybVerificationId): array
    {
        $cycles = count($this->findCycles($graph));
        $shells = count($this->detectShellCompanies($kybVerificationId));
        $patterns = $this->detectMoneyLaunderingPatterns($kybVerificationId);
        $depth = $this->calculateMaxDepth($graph);

        // Calculate weighted score
        $score = 0;
        $score += $cycles * 30; // Circular ownership is high risk
        $score += $shells * 20; // Shell companies
        $score += count($patterns) * 25; // ML patterns
        $score += max(0, ($depth - 3) * 10); // Depth beyond 3 layers

        $score = min(100, $score);

        $level = match (true) {
            $score >= self::CRITICAL_RISK_THRESHOLD => 'critical',
            $score >= self::HIGH_RISK_THRESHOLD => 'high',
            $score >= self::MEDIUM_RISK_THRESHOLD => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'factors' => [
                'circular_ownership' => $cycles,
                'shell_companies' => $shells,
                'ml_patterns' => count($patterns),
                'graph_depth' => $depth,
            ],
        ];
    }

    private function buildGraphFromChain(array $uboChain): array
    {
        $graph = [];

        foreach ($uboChain as $entity) {
            $nodeId = $entity['id'] ?? $entity['entity_inn'] ?? md5($entity['entity_name']);

            $graph[$nodeId] = [
                'id' => $nodeId,
                'name' => $entity['entity_name'],
                'inn' => $entity['entity_inn'] ?? null,
                'type' => $entity['entity_type'] ?? 'company',
                'ownership_percentage' => $entity['ownership_percentage'] ?? 0,
                'is_ultimate_beneficial_owner' => $entity['is_ultimate_beneficial_owner'] ?? false,
                'parents' => $entity['parent_id'] ?? null,
            ];
        }

        return $graph;
    }

    private function findCycles(array $graph): array
    {
        $cycles = [];
        $visited = [];
        $recursionStack = [];

        foreach ($graph as $nodeId => $node) {
            if (! isset($visited[$nodeId])) {
                $this->dfsFindCycle($graph, $nodeId, $visited, $recursionStack, $cycles);
            }
        }

        return $cycles;
    }

    private function dfsFindCycle(array $graph, string $nodeId, array &$visited, array &$recursionStack, array &$cycles): bool
    {
        $visited[$nodeId] = true;
        $recursionStack[$nodeId] = true;

        if (isset($graph[$nodeId]['parents'])) {
            $parentId = $graph[$nodeId]['parents'];
            if (isset($graph[$parentId])) {
                if (! isset($visited[$parentId])) {
                    if ($this->dfsFindCycle($graph, $parentId, $visited, $recursionStack, $cycles)) {
                        return true;
                    }
                } elseif (isset($recursionStack[$parentId])) {
                    $cycles[] = [
                        'cycle' => array_keys($recursionStack),
                        'detected_at' => $nodeId,
                    ];

                    return true;
                }
            }
        }

        unset($recursionStack[$nodeId]);

        return false;
    }

    private function calculateMaxDepth(array $graph): int
    {
        $maxDepth = 0;

        foreach ($graph as $nodeId => $node) {
            $depth = $this->calculateNodeDepth($graph, $nodeId);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }

    private function calculateNodeDepth(array $graph, string $nodeId, int $depth = 0): int
    {
        if (! isset($graph[$nodeId]['parents']) || ! $graph[$nodeId]['parents']) {
            return $depth;
        }

        $parentId = $graph[$nodeId]['parents'];
        if (! isset($graph[$parentId])) {
            return $depth;
        }

        return $this->calculateNodeDepth($graph, $parentId, $depth + 1);
    }

    private function detectRapidTransfers(array $uboChain): array
    {
        // In a real implementation, this would check ownership transfer dates
        // For now, return empty array
        return [];
    }

    private function isShellCompany(array $entity): bool
    {
        // Shell company indicators (simplified)
        $indicators = 0;

        // No employees
        if (empty($entity['employee_count'] ?? null)) {
            $indicators++;
        }

        // No revenue
        if (empty($entity['revenue'] ?? null) || ($entity['revenue'] ?? 0) < 10000) {
            $indicators++;
        }

        // No physical address
        if (empty($entity['address'] ?? null)) {
            $indicators++;
        }

        // High ownership percentage (typical for shells)
        if (($entity['ownership_percentage'] ?? 0) > 90) {
            $indicators++;
        }

        return $indicators >= 2;
    }

    private function findBeneficiaryClusters(array $graph): array
    {
        // Group entities by common owners
        $clusters = [];

        foreach ($graph as $nodeId => $node) {
            if ($node['is_ultimate_beneficial_owner']) {
                $clusters[] = [
                    'ubo' => $node,
                    'controlled_entities' => $this->findControlledEntities($graph, $nodeId),
                ];
            }
        }

        return $clusters;
    }

    private function findControlledEntities(array $graph, string $ownerId): array
    {
        $controlled = [];

        foreach ($graph as $nodeId => $node) {
            if ($node['parents'] === $ownerId) {
                $controlled[] = $node;
                $controlled = array_merge($controlled, $this->findControlledEntities($graph, $nodeId));
            }
        }

        return $controlled;
    }

    private function getUBOChain(int $kybVerificationId): array
    {
        $verification = KYBVerification::with('uboChain')->find($kybVerificationId);

        if (! $verification) {
            return [];
        }

        return $verification->ubo_chain ?? [];
    }

    private function storeAnalysisResults(int $kybVerificationId, array $analysis, string $correlationId): void
    {
        LinkAnalysisResult::updateOrCreate(
            [
                'kyb_verification_id' => $kybVerificationId,
            ],
            [
                'circular_ownership' => $analysis['circular_ownership'],
                'shell_companies' => $analysis['shell_companies'],
                'money_laundering_patterns' => $analysis['money_laundering_patterns'],
                'beneficiary_clusters' => $analysis['beneficiary_clusters'],
                'network_risk_score' => $analysis['network_risk_score']['score'],
                'network_risk_level' => $analysis['network_risk_score']['level'],
                'network_risk_factors' => $analysis['network_risk_score']['factors'],
                'analyzed_at' => CarbonImmutable::now(),
                'correlation_id' => $correlationId,
            ]
        );

        // Update KYB verification with link analysis
        KYBVerification::where('id', $kybVerificationId)->update([
            'link_analysis' => $analysis,
        ]);

        // Audit log
        $this->audit->record(
            action: 'link_analysis_completed',
            subjectType: KYBVerification::class,
            subjectId: $kybVerificationId,
            newValues: [
                'network_risk_score' => $analysis['network_risk_score']['score'],
                'network_risk_level' => $analysis['network_risk_score']['level'],
            ],
            correlationId: $correlationId,
        );
    }
}
