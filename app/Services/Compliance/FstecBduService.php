<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Models\FstecThreat;
use App\Models\FstecVulnerability;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

/**
 * FSTEC BDU Service
 * 
 * Synchronizes threats and vulnerabilities from the FSTEC Threat Database (БДУ ФСТЭК).
 * This service keeps the threat model up-to-date with the latest security information.
 * 
 * Reference: https://bdu.fstec.ru/
 * Methodology: Методика ФСТЭК 2021
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: Required for 152-FZ compliance and Roskomnadzor audits.
 */
final readonly class FstecBduService
{
    use WithAuditLogging;

    private const BDU_API_URL = 'https://bdu.fstec.ru/api/v1';
    private const SYNC_TIMEOUT = 300; // 5 minutes

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpFactory $http,
        private readonly AuditService $audit,
    ) {}
/**
     * Sync all relevant threats from BDU
     */
    public function syncThreats(): array
    {
        $this->logger->info('Starting BDU threats sync');

        try {
            $response = Http::timeout(self::SYNC_TIMEOUT)
                ->get(self::BDU_API_URL.'/threats');
$this->h->
            if (! $response->successful()) {
                throw new \RuntimeException('Failed to fetch threats from BDU: '.$response->status());
            }

            $threats = $response->json('data', []);
            $synced = 0;
            $updated = 0;
            $created = 0;

            foreach ($threats as $threatData) {
                if (! $this->isThreatRelevant($threatData)) {
                    continue;
                }

                $threat = FstecThreat::withTrashed()
                    ->where('fstec_id', $threatData['id'])
                    ->first();

                if ($threat) {
                    $this->updateThreat($threat, $threatData);
                    $updated++;
                } else {
                    $this->createThreat($threatData);
                    $created++;
                }

                $synced++;
            }

            $this->logger->info('BDU threats sync completed', [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);

            return [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('BDU threats sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync all relevant vulnerabilities from BDU
     */
    public function syncVulnerabilities(): array
    {
        $this->logger->info('Starting BDU vulnerabilities sync');

        try {
            $response = Http::timeout(self::SYNC_TIMEOUT)
                ->get(self::BDU_API_URL.'/vulnerabilities');
$this->h->
            if (! $response->successful()) {
                throw new \RuntimeException('Failed to fetch vulnerabilities from BDU: '.$response->status());
            }

            $vulnerabilities = $response->json('data', []);
            $synced = 0;
            $updated = 0;
            $created = 0;

            foreach ($vulnerabilities as $vulnData) {
                if (! $this->isVulnerabilityRelevant($vulnData)) {
                    continue;
                }

                $vulnerability = FstecVulnerability::withTrashed()
                    ->where('fstec_id', $vulnData['id'])
                    ->first();

                if ($vulnerability) {
                    $this->updateVulnerability($vulnerability, $vulnData);
                    $updated++;
                } else {
                    $this->createVulnerability($vulnData);
                    $created++;
                }

                $synced++;
            }

            $this->logger->info('BDU vulnerabilities sync completed', [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);

            return [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('BDU vulnerabilities sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Full sync (threats + vulnerabilities)
     */
    public function syncAll(): array
    {
        $threatsResult = $this->syncThreats();
        $vulnerabilitiesResult = $this->syncVulnerabilities();

        return [
            'threats' => $threatsResult,
            'vulnerabilities' => $vulnerabilitiesResult,
        ];
    }

    /**
     * Check if threat is relevant to CatVRF
     */
    private function isThreatRelevant(array $threatData): bool
    {
        $relevantKeywords = [
            'web', 'application', 'sql', 'injection', 'xss', 'csrf',
            'biometric', 'behavioral', 'personal data', 'персональные данные',
            'insider', 'internal', 'внутренний',
            'multi-tenant', 'multitenant',
            'laravel', 'php', 'postgresql', 'mysql',
        ];

        $name = strtolower($threatData['name'] ?? '');
        $description = strtolower($threatData['description'] ?? '');

        foreach ($relevantKeywords as $keyword) {
            if (str_contains($name, $keyword) || str_contains($description, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if vulnerability is relevant to CatVRF
     */
    private function isVulnerabilityRelevant(array $vulnData): bool
    {
        $relevantProducts = [
            'laravel', 'php', 'postgresql', 'mysql', 'nginx',
            'livewire', 'filament', 'vue', 'javascript',
        ];

        $affectedProducts = array_map('strtolower', $vulnData['affected_products'] ?? []);

        foreach ($relevantProducts as $product) {
            if (in_array($product, $affectedProducts, true)) {
                return true;
            }
        }

        // Also check CVSS score
        $cvssScore = $vulnData['cvss_score'] ?? 0;
        if ($cvssScore >= 7.0) {
            return true;
        }

        return false;
    }

    /**
     * Create threat from BDU data
     */
    private function createThreat(array $data): void
    {
        FstecThreat::create([
            'fstec_id' => $data['id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'threat_type' => $data['type'] ?? 'unknown',
            'threat_class' => $data['class'] ?? 'external',
            'is_relevant' => true,
            'relevance_reason' => 'Detected by BDU sync based on keywords',
            'probability' => $this->mapProbability($data['probability'] ?? 'medium'),
            'impact' => $this->mapImpact($data['impact'] ?? 'medium'),
            'risk_level' => $this->mapRiskLevel($data['risk'] ?? 'medium'),
            'affected_assets' => $data['assets'] ?? [],
            'affected_systems' => $data['systems'] ?? [],
            'mitigation_measures' => $data['mitigation'] ?? [],
            'is_mitigated' => false,
            'source' => 'bdu.fstec.ru',
            'source_updated_at' => $data['updated_at'] ?? now(),
            'synced_at' => now(),
        ]);
    }

    /**
     * Update existing threat
     */
    private function updateThreat(FstecThreat $threat, array $data): void
    {
        $threat->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $threat->description,
            'probability' => $this->mapProbability($data['probability'] ?? 'medium'),
            'impact' => $this->mapImpact($data['impact'] ?? 'medium'),
            'risk_level' => $this->mapRiskLevel($data['risk'] ?? 'medium'),
            'affected_assets' => $data['assets'] ?? $threat->affected_assets,
            'affected_systems' => $data['systems'] ?? $threat->affected_systems,
            'mitigation_measures' => $data['mitigation'] ?? $threat->mitigation_measures,
            'source_updated_at' => $data['updated_at'] ?? now(),
            'synced_at' => now(),
        ]);

        // Restore if soft-deleted
        if ($threat->trashed()) {
            $threat->restore();
        }
    }

    /**
     * Create vulnerability from BDU data
     */
    private function createVulnerability(array $data): void
    {
        FstecVulnerability::create([
            'fstec_id' => $data['id'],
            'cve_id' => $data['cve_id'] ?? null,
            'cvss_score' => $data['cvss_score'] ?? null,
            'cvss_vector' => $data['cvss_vector'] ?? null,
            'severity' => $this->mapSeverity($data['cvss_score'] ?? 0),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'affected_products' => $data['affected_products'] ?? [],
            'affected_versions' => $data['affected_versions'] ?? [],
            'is_relevant' => true,
            'relevance_reason' => 'Detected by BDU sync based on products/CVSS',
            'status' => 'open',
            'is_patched' => false,
            'mitigation' => $data['mitigation'] ?? null,
            'mitigation_type' => $data['mitigation_type'] ?? null,
            'source' => 'bdu.fstec.ru',
            'disclosed_at' => $data['disclosed_at'] ?? null,
            'source_updated_at' => $data['updated_at'] ?? now(),
            'synced_at' => now(),
        ]);
    }

    /**
     * Update existing vulnerability
     */
    private function updateVulnerability(FstecVulnerability $vulnerability, array $data): void
    {
        $vulnerability->update([
            'cvss_score' => $data['cvss_score'] ?? $vulnerability->cvss_score,
            'cvss_vector' => $data['cvss_vector'] ?? $vulnerability->cvss_vector,
            'severity' => $this->mapSeverity($data['cvss_score'] ?? $vulnerability->cvss_score ?? 0),
            'description' => $data['description'] ?? $vulnerability->description,
            'affected_products' => $data['affected_products'] ?? $vulnerability->affected_products,
            'affected_versions' => $data['affected_versions'] ?? $vulnerability->affected_versions,
            'mitigation' => $data['mitigation'] ?? $vulnerability->mitigation,
            'source_updated_at' => $data['updated_at'] ?? now(),
            'synced_at' => now(),
        ]);

        // Restore if soft-deleted
        if ($vulnerability->trashed()) {
            $vulnerability->restore();
        }
    }

    /**
     * Map probability from BDU format to internal format
     */
    private function mapProbability(string $probability): string
    {
        return match (strtolower($probability)) {
            'очень низкая', 'very low' => 'very_low',
            'низкая', 'low' => 'low',
            'средняя', 'medium' => 'medium',
            'высокая', 'high' => 'high',
            'очень высокая', 'very high' => 'very_high',
            default => 'medium',
        };
    }

    /**
     * Map impact from BDU format to internal format
     */
    private function mapImpact(string $impact): string
    {
        return match (strtolower($impact)) {
            'очень низкий', 'very low' => 'very_low',
            'низкий', 'low' => 'low',
            'средний', 'medium' => 'medium',
            'высокий', 'high' => 'high',
            'очень высокий', 'very high' => 'very_high',
            default => 'medium',
        };
    }

    /**
     * Map risk level from BDU format to internal format
     */
    private function mapRiskLevel(string $risk): string
    {
        return match (strtolower($risk)) {
            'низкий', 'low' => 'low',
            'средний', 'medium' => 'medium',
            'высокий', 'high' => 'high',
            'критический', 'critical' => 'critical',
            default => 'medium',
        };
    }

    /**
     * Map CVSS score to severity
     */
    private function mapSeverity(float $cvssScore): string
    {
        return match (true) {
            $cvssScore === 0.0 => 'none',
            $cvssScore < 4.0 => 'low',
            $cvssScore < 7.0 => 'medium',
            $cvssScore < 9.0 => 'high',
            default => 'critical',
        };
    }

    /**
     * Get statistics about synced threats and vulnerabilities
     */
    public function getStatistics(): array
    {
        return [
            'threats' => [
                'total' => FstecThreat::count(),
                'relevant' => FstecThreat::relevant()->count(),
                'not_mitigated' => FstecThreat::relevant()->notMitigated()->count(),
                'high_risk' => FstecThreat::relevant()->highRisk()->count(),
            ],
            'vulnerabilities' => [
                'total' => FstecVulnerability::count(),
                'relevant' => FstecVulnerability::relevant()->count(),
                'not_patched' => FstecVulnerability::relevant()->notPatched()->count(),
                'critical' => FstecVulnerability::relevant()->critical()->count(),
                'stale' => FstecVulnerability::relevant()->notPatched()->get()->filter(fn ($v) => $v->isStale())->count(),
            ],
            'last_sync' => [
                'threats' => FstecThreat::max('synced_at'),
                'vulnerabilities' => FstecVulnerability::max('synced_at'),
            ],
        ];
    }
}
