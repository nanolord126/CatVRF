<?php

declare(strict_types=1);

namespace App\Jobs\Payments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Application\Services\AMLService;
use Modules\Payment\Domain\Repositories\AMLCheckRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Exception;

/**
 * Job to report suspicious transactions to Rosfinmonitoring (ФЗ-115).
 * 
 * This job processes reportable AML checks and sends them to Rosfinmonitoring
 * via their API. Runs asynchronously to avoid blocking payment processing.
 */
final class AMLReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private string $amlCheckUuid,
    ) {
        $this->onQueue(config('payment_compliance.general.bigdata_integration.queue_connection', 'redis'));
    }

    public function handle(
        AMLCheckRepositoryInterface $amlRepository,
        AMLService $amlService,
    ): void {
        $amlCheck = $amlRepository->findByUuid($this->amlCheckUuid);

        if (! $amlCheck) {
            Log::warning('AML check not found for reporting', [
                'aml_check_uuid' => $this->amlCheckUuid,
            ]);
            return;
        }

        if ($amlCheck->isReportedToRosfinmonitoring) {
            Log::info('AML check already reported to Rosfinmonitoring', [
                'aml_check_uuid' => $this->amlCheckUuid,
            ]);
            return;
        }

        if (! $amlCheck->isReportable()) {
            Log::info('AML check not reportable', [
                'aml_check_uuid' => $this->amlCheckUuid,
                'risk_level' => $amlCheck->riskLevel,
                'amount_kopecks' => $amlCheck->amountKopecks,
            ]);
            return;
        }

        try {
            $this->sendToRosfinmonitoring($amlCheck);
            
            // Mark as reported
            $amlService->markAsReported($this->amlCheckUuid);

            Log::info('AML check reported to Rosfinmonitoring successfully', [
                'aml_check_uuid' => $this->amlCheckUuid,
                'user_id' => $amlCheck->userId,
                'amount_kopecks' => $amlCheck->amountKopecks,
                'risk_level' => $amlCheck->riskLevel,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to report AML check to Rosfinmonitoring', [
                'aml_check_uuid' => $this->amlCheckUuid,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('AML report failed after all retries', [
                    'aml_check_uuid' => $this->amlCheckUuid,
                    'user_id' => $amlCheck->userId,
                    'amount_kopecks' => $amlCheck->amountKopecks,
                ]);
            }

            $this->release(60 * $this->attempts()); // Exponential backoff
        }
    }

    private function sendToRosfinmonitoring($amlCheck): void
    {
        $apiUrl = config('payment_compliance.fz115.rosfinmonitoring.api_url');
        $apiKey = config('payment_compliance.fz115.rosfinmonitoring.api_key');
        $organizationInn = config('payment_compliance.fz115.rosfinmonitoring.organization_inn');

        if (! $apiUrl || ! $apiKey || ! $organizationInn) {
            throw new Exception('Rosfinmonitoring API credentials not configured');
        }

        $payload = [
            'report_id' => $amlCheck->uuid,
            'report_date' => CarbonImmutable::now()->format('Y-m-d'),
            'organization_inn' => $organizationInn,
            'transaction' => [
                'user_id' => $amlCheck->userId,
                'amount_kopecks' => $amlCheck->amountKopecks,
                'currency' => $amlCheck->currency,
                'transaction_date' => $amlCheck->checkedAt->format('Y-m-d H:i:s'),
                'risk_score' => $amlCheck->riskScore,
                'risk_level' => $amlCheck->riskLevel,
                'kyc_level' => $amlCheck->kycLevel,
                'reason' => $amlCheck->reason,
            ],
            'check_factors' => $amlCheck->checkFactors,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($apiUrl, $payload);

        if (! $response->successful()) {
            throw new Exception("Rosfinmonitoring API error: {$response->body()}");
        }

        $data = $response->json();

        if (! isset($data['report_accepted']) || ! $data['report_accepted']) {
            throw new Exception('Rosfinmonitoring rejected the report');
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('AMLReportJob failed permanently', [
            'aml_check_uuid' => $this->amlCheckUuid,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
