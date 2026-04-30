<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use App\Models\SanctionsScreening;
use App\Models\UBOChain;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class SanctionsScreeningService
{
    use WithAuditLogging;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly Repository $config,
        private readonly AuditService $auditService,
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
            if (! empty($entity['director_name'])) {
                $directorEntity = [
                    'entity_name' => $entity['director_name'],
                    'entity_inn' => $entity['director_inn'] ?? null,
                    'entity_type' => 'individual',
                ];
                $results[] = $this->screenEntity(
                    $kybVerificationId,
                    $directorEntity,
                    'director',
                    $correlationId,
                    null, // ubo_chain_id will be set if we can match it
                    $entity['entity_inn'] // Try to match by INN
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
        ?int $uboChainId = null,
        ?string $uboEntityInn = null
    ): array {
        // Try to find UBO chain ID if not provided
        if ($uboChainId === null && $uboEntityInn !== null) {
            $uboChainRecord = UBOChain::where('kyb_verification_id', $kybVerificationId)
                ->where('entity_inn', $uboEntityInn)
                ->first();
            $uboChainId = $uboChainRecord?->id;
        }

        // Screen against Росфинмониторинг
        $rosfinResult = $this->screenRosfinmonitoring($entity);

        // Screen against OFAC
        $ofacResult = $this->screenOFAC($entity);

        // Screen against EU
        $euResult = $this->screenEU($entity);

        // Aggregate results
        $aggregateStatus = $this->aggregateStatus([$rosfinResult, $ofacResult, $euResult]);

        // Store result
        $screening = SanctionsScreening::create([
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
            'screened_at' => CarbonImmutable::now(),
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
        $apiUrl = $this->config->get('kyb.rosfinmonitoring.api_url');

        if (! $apiUrl) {
            return ['status' => 'not_configured'];
        }

        try {
            $response = $this->http->timeout(30)
                ->get($apiUrl, [
                    'inn' => $entity['entity_inn'] ?? null,
                    'name' => $entity['entity_name'],
                ]);

            if (! $response->successful()) {
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
            $this->log->error('Росфинмониторинг screening error', [
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
        $apiKey = $this->config->get('kyb.ofac.api_key');
        $apiUrl = $this->config->get('kyb.ofac.api_url');

        if (! $apiKey || ! $apiUrl) {
            return ['status' => 'not_configured'];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->get($apiUrl.'/search', [
                    'name' => $entity['entity_name'],
                    'type' => $entity['entity_type'],
                ]);

            if (! $response->successful()) {
                return ['status' => 'error', 'message' => 'API error'];
            }

            $data = $response->json();

            if (empty($data['results'])) {
                return ['status' => 'clean'];
            }

            return [
                'status' => 'match',
                'results' => $data['results'],
            ];
        } catch (\Throwable $e) {
            $this->log->error('OFAC screening error', [
                'entity_name' => $entity['entity_name'],
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error'];
        }
    }

    /**
     * Screen against EU
     */
    private function screenEU(array $entity): array
    {
        $apiUrl = $this->config->get('kyb.eu_sanctions.api_url');

        if (! $apiUrl) {
            return ['status' => 'not_configured'];
        }

        try {
            $response = $this->http->timeout(30)
                ->get($apiUrl.'/search', [
                    'name' => $entity['entity_name'],
                ]);

            if (! $response->successful()) {
                return ['status' => 'error', 'message' => 'API error'];
            }

            $data = $response->json();

            if (empty($data['results'])) {
                return ['status' => 'clean'];
            }

            return [
                'status' => 'match',
                'results' => $data['results'],
            ];
        } catch (\Throwable $e) {
            $this->log->error('EU sanctions screening error', [
                'entity_name' => $entity['entity_name'],
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error'];
        }
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
