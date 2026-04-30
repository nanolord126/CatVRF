<?php

declare(strict_types=1);

namespace App\Jobs\Payments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Domain\Repositories\AMLCheckRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\CarbonImmutable;
use Exception;

/**
 * Scheduled job to batch report to Rosfinmonitoring (ФЗ-115).
 * 
 * This job runs on a schedule (typically daily) to batch report
 * all reportable AML checks to Rosfinmonitoring.
 */
final class RosfinmonitoringReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // 5 minutes for batch processing

    public function __construct()
    {
        $this->onQueue(config('payment_compliance.general.bigdata_integration.queue_connection', 'redis'));
    }

    public function handle(AMLCheckRepositoryInterface $amlRepository): void
    {
        if (! config('payment_compliance.fz115.rosfinmonitoring.enabled')) {
            Log::info('Rosfinmonitoring reporting is disabled');
            return;
        }

        $reportableChecks = $amlRepository->findReportable();

        if (empty($reportableChecks)) {
            Log::info('No reportable AML checks found');
            return;
        }

        Log::info('Found reportable AML checks for Rosfinmonitoring', [
            'count' => count($reportableChecks),
        ]);

        $apiUrl = config('payment_compliance.fz115.rosfinmonitoring.api_url');
        $apiKey = config('payment_compliance.fz115.rosfinmonitoring.api_key');
        $organizationInn = config('payment_compliance.fz115.rosfinmonitoring.organization_inn');

        if (! $apiUrl || ! $apiKey || ! $organizationInn) {
            Log::error('Rosfinmonitoring API credentials not configured');
            return;
        }

        $batchPayload = [
            'batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'report_date' => CarbonImmutable::now()->format('Y-m-d'),
            'organization_inn' => $organizationInn,
            'reports' => [],
        ];

        $reportedCount = 0;
        $failedCount = 0;

        foreach ($reportableChecks as $amlCheck) {
            if ($amlCheck->isReportedToRosfinmonitoring) {
                continue;
            }

            $batchPayload['reports'][] = [
                'report_id' => $amlCheck->uuid,
                'user_id' => $amlCheck->userId,
                'amount_kopecks' => $amlCheck->amountKopecks,
                'currency' => $amlCheck->currency,
                'transaction_date' => $amlCheck->checkedAt->format('Y-m-d H:i:s'),
                'risk_score' => $amlCheck->riskScore,
                'risk_level' => $amlCheck->riskLevel,
                'kyc_level' => $amlCheck->kycLevel,
                'reason' => $amlCheck->reason,
                'check_factors' => $amlCheck->checkFactors,
            ];

            $reportedCount++;
        }

        if (empty($batchPayload['reports'])) {
            Log::info('All checks already reported to Rosfinmonitoring');
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($apiUrl, $batchPayload);

            if (! $response->successful()) {
                throw new Exception("Rosfinmonitoring API error: {$response->body()}");
            }

            $data = $response->json();

            if (! isset($data['batch_accepted']) || ! $data['batch_accepted']) {
                throw new Exception('Rosfinmonitoring rejected the batch report');
            }

            // Mark all checks as reported
            foreach ($reportableChecks as $amlCheck) {
                if (! $amlCheck->isReportedToRosfinmonitoring) {
                    $updatedCheck = $amlCheck->markAsReported();
                    $amlRepository->save($updatedCheck);
                }
            }

            Log::info('Rosfinmonitoring batch report successful', [
                'batch_id' => $batchPayload['batch_id'],
                'reported_count' => $reportedCount,
                'failed_count' => $failedCount,
            ]);

        } catch (Exception $e) {
            $failedCount = count($batchPayload['reports']);

            Log::error('Rosfinmonitoring batch report failed', [
                'batch_id' => $batchPayload['batch_id'],
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'failed_count' => $failedCount,
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('Rosfinmonitoring batch report failed after all retries', [
                    'batch_id' => $batchPayload['batch_id'],
                    'failed_count' => $failedCount,
                ]);
            }

            $this->release(300); // Retry after 5 minutes
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('RosfinmonitoringReportJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
