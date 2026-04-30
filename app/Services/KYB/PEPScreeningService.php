<?php

declare(strict_types=1);

namespace App\Services\KYB;

use App\Models\PEPRecord;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;

final readonly class PEPScreeningService
{
    use WithAuditLogging;

    private const COOLING_OFF_PERIOD_MONTHS = 18;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly Repository $config,
    ) {}

    /**
     * Screen all entities in UBO chain for PEP status
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array {
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
            'screened_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        // Update with risk scoring
        $this->calculatePEPRisk($pepRecord);

        // Audit log
        $this->audit->record(
            action: 'pep_screening_completed',
            subjectType: PEPRecord::class,
            subjectId: $pepRecord->id,
            newValues: [
                'kyb_verification_id' => $kybVerificationId,
                'entity_name' => $entity['entity_name'],
                'pep_status' => $worldCheckResult['pep_status'],
                'provider' => $worldCheckResult['provider'],
            ],
            correlationId: $correlationId,
        );

        return [
            'entity_name' => $entity['entity_name'],
            'pep_status' => $worldCheckResult['pep_status'],
            'pep_record_id' => $pepRecord->id,
            'risk_level' => $pepRecord->pep_risk_level,
        ];
    }

    /**
     * Re-screen existing PEP records (monthly)
     */
    public function reScreenPEPs(string $correlationId = ''): array
    {
        $activePEPs = PEPRecord::where('pep_status', 'pep')
            ->where('screened_at', '<=', CarbonImmutable::now()->subMonth())
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
        $apiKey = $this->config->get('kyb.pep_screening.world_check.api_key');
        $apiUrl = $this->config->get('kyb.pep_screening.world_check.api_url');

        if (! $apiKey || ! $apiUrl) {
            return ['status' => 'not_configured', 'pep_status' => 'unknown', 'provider' => 'world_check'];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->post($apiUrl.'/search', [
                    'name' => $entity['entity_name'],
                    'date_of_birth' => $entity['entity_dob'] ?? null,
                    'identification_number' => $entity['entity_inn'] ?? null,
                ]);

            if (! $response->successful()) {
                $this->log->warning('World-Check PEP screening failed', [
                    'entity_name' => $entity['entity_name'],
                    'status' => $response->status(),
                ]);

                return ['status' => 'error', 'pep_status' => 'unknown', 'provider' => 'world_check'];
            }

            $data = $response->json();

            if (empty($data['matches'])) {
                return [
                    'status' => 'clean',
                    'pep_status' => 'not_pep',
                    'provider' => 'world_check',
                ];
            }

            $match = $data['matches'][0];

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
            $this->log->error('World-Check PEP screening error', [
                'entity_name' => $entity['entity_name'],
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'pep_status' => 'unknown', 'provider' => 'world_check'];
        }
    }

    private function screenKontur(array $entity): array
    {
        $apiKey = $this->config->get('kyb.pep_screening.kontur_focus.api_key');
        $apiUrl = $this->config->get('kyb.pep_screening.kontur_focus.api_url');

        if (! $apiKey || ! $apiUrl) {
            return ['status' => 'not_configured', 'pep_status' => 'unknown', 'provider' => 'kontur'];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->get($apiUrl.'/pep', [
                    'inn' => $entity['entity_inn'] ?? null,
                    'name' => $entity['entity_name'],
                ]);

            if (! $response->successful()) {
                $this->log->warning('Kontur PEP screening failed', [
                    'entity_name' => $entity['entity_name'],
                    'status' => $response->status(),
                ]);

                return ['status' => 'error', 'pep_status' => 'unknown', 'provider' => 'kontur'];
            }

            $data = $response->json();

            if (empty($data['pep'])) {
                return [
                    'status' => 'clean',
                    'pep_status' => 'not_pep',
                    'provider' => 'kontur',
                ];
            }

            $pepData = $data['pep'];

            return [
                'status' => 'match',
                'pep_status' => $this->determinePEPStatus($pepData),
                'position' => $pepData['position'] ?? null,
                'country' => $pepData['country'] ?? 'RU',
                'start_date' => $pepData['start_date'] ?? null,
                'end_date' => $pepData['end_date'] ?? null,
                'category' => $pepData['category'] ?? null,
                'provider' => 'kontur',
                'raw_match' => $pepData,
            ];
        } catch (\Throwable $e) {
            $this->log->error('Kontur PEP screening error', [
                'entity_name' => $entity['entity_name'],
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'pep_status' => 'unknown', 'provider' => 'kontur'];
        }
    }

    private function determinePEPStatus(array $match): string
    {
        if (isset($match['end_date']) && ! empty($match['end_date'])) {
            // Check if within cooling-off period
            $endDate = Carbon::parse($match['end_date']);

            if ($endDate->addMonths(self::COOLING_OFF_PERIOD_MONTHS)->isFuture()) {
                return 'former_pep';
            }

            return 'not_pep';
        }

        return 'pep';
    }

    private function calculatePEPRisk(PEPRecord $pepRecord): void
    {
        $score = 0;
        $level = 'low';

        if ($pepRecord->pep_status === 'pep') {
            $score += 50;

            // Higher risk for head of state/government
            if (in_array($pepRecord->pep_category, ['head_of_state', 'senior_government'], true)) {
                $score += 30;
            }

            // Higher risk for certain countries
            if (in_array($pepRecord->pep_country, ['RU', 'CN', 'IR', 'KP'], true)) {
                $score += 20;
            }
        } elseif ($pepRecord->pep_status === 'former_pep') {
            $score += 20;
        }

        $score = min(100, $score);

        $level = match (true) {
            $score >= 70 => 'critical',
            $score >= 50 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };

        $pepRecord->update([
            'pep_risk_score' => $score,
            'pep_risk_level' => $level,
            'requires_enhanced_due_diligence' => $level === 'high' || $level === 'critical',
        ]);
    }
}
