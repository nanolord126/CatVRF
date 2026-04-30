<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Psr\Log\LoggerInterface;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use App\Models\UBOChain;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class UBOAnalysisService
{
    use WithAuditLogging;

    private const MAX_LEVELS = 5;

    private const UBO_THRESHOLD = 25.0; // 25% ownership = UBO

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly Repository $config,
        private readonly AuditService $auditService,
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

        // If both fail, return placeholder
        if (empty($chain)) {
            $chain = $this->createPlaceholderChain($inn);
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
        $apiKey = $this->config->get('kyb.kontur_focus.api_key');
        $apiUrl = $this->config->get('kyb.kontur_focus.api_url');

        if (! $apiKey) {
            $this->log->warning('Kontur.Focus API key not configured');

            return [];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->get($apiUrl.'/ubo', [
                    'inn' => $inn,
                    'depth' => self::MAX_LEVELS,
                ]);

            if (! $response->successful()) {
                $this->log->warning('Kontur.Focus UBO extraction failed', [
                    'inn' => $inn,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            // Parse and build chain
            return $this->parseKonturResponse($data);
        } catch (\Throwable $e) {
            $this->log->error('Kontur.Focus UBO extraction error', [
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
        $apiKey = $this->config->get('kyb.spark_interfax.api_key');
        $apiUrl = $this->config->get('kyb.spark_interfax.api_url');

        if (! $apiKey) {
            $this->log->warning('Spark Interfax API key not configured');

            return [];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->get($apiUrl.'/ubo', [
                    'inn' => $inn,
                    'depth' => self::MAX_LEVELS,
                ]);

            if (! $response->successful()) {
                $this->log->warning('Spark UBO extraction failed', [
                    'inn' => $inn,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            return $this->parseSparkResponse($data);
        } catch (\Throwable $e) {
            $this->log->error('Spark UBO extraction error', [
                'inn' => $inn,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create placeholder chain when external APIs fail
     */
    private function createPlaceholderChain(string $inn): array
    {
        return [
            [
                'level' => 0,
                'entity_type' => 'company',
                'entity_name' => 'Company (INN: '.$inn.')',
                'entity_inn' => $inn,
                'ownership_percentage' => 100.0,
                'is_ultimate_beneficial_owner' => false,
                'director_name' => null,
                'director_inn' => null,
            ],
        ];
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
            'entity_name' => $data['name']['short'] ?? $data['name']['full'] ?? 'Unknown',
            'entity_inn' => $data['inn'] ?? null,
            'entity_ogrn' => $data['ogrn'] ?? null,
            'ownership_percentage' => 100.0,
            'is_ultimate_beneficial_owner' => false,
            'director_name' => $data['management']['name'] ?? null,
            'director_inn' => $data['management']['inn'] ?? null,
        ];

        // Extract ownership structure recursively
        if (isset($data['shareholders']) && is_array($data['shareholders'])) {
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
     * Parse Spark response
     */
    private function parseSparkResponse(array $data): array
    {
        // Similar to Kontur parsing
        return $this->parseKonturResponse($data);
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
                'entity_type' => ($shareholder['type'] ?? 'COMPANY') === 'INDIVIDUAL' ? 'individual' : 'company',
                'entity_name' => $shareholder['name'] ?? 'Unknown',
                'entity_inn' => $shareholder['inn'] ?? null,
                'entity_ogrn' => $shareholder['ogrn'] ?? null,
                'ownership_percentage' => floatval($shareholder['share'] ?? 0),
                'is_ultimate_beneficial_owner' => false,
                'director_name' => null,
                'director_inn' => null,
            ];

            $chain[] = $entity;

            // Recurse for company shareholders
            if ($entity['entity_type'] === 'company' && isset($shareholder['shareholders']) && is_array($shareholder['shareholders'])) {
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
            UBOChain::create([
                'kyb_verification_id' => $kybVerificationId,
                'level' => $entity['level'],
                'entity_type' => $entity['entity_type'],
                'entity_name' => $entity['entity_name'],
                'entity_inn' => $entity['entity_inn'],
                'entity_ogrn' => $entity['entity_ogrn'] ?? null,
                'ownership_percentage' => $entity['ownership_percentage'],
                'is_ultimate_beneficial_owner' => $entity['is_ultimate_beneficial_owner'],
                'director_name' => $entity['director_name'],
                'director_inn' => $entity['director_inn'],
                'raw_data' => $entity,
            ]);
        }

        $this->log->$this->logger->info('UBO chain stored', [
            'kyb_verification_id' => $kybVerificationId,
            'entities_count' => count($chain),
            'correlation_id' => $correlationId,
        ]);
    }
}
