<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Adapters;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Interfaces\CloudBillingAdapterInterface;

/**
 * GCP Billing Adapter
 *
 * Fetches billing data from Google Cloud Billing / BigQuery billing export.
 */
final class GCPBillingAdapter implements CloudBillingAdapterInterface
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $billingAccountId,
        private readonly string $accessToken = '',
    ) {}

    public function getDailyBilling(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("https://cloudbilling.googleapis.com/v1/{$this->billingAccountId}/billingInfo");

            return $this->queryBigQueryExport($startDate, $endDate);
        } catch (\Throwable $e) {
            Log::error('GCP Billing: fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getMonthlyForecast(): float
    {
        return 0;
    }

    public function getOptimizationRecommendations(): array
    {
        return [];
    }

    public function getResourceTags(): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->get("https://cloudbilling.googleapis.com/v1/{$this->billingAccountId}");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function queryBigQueryExport(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        // In production, query GCP billing export table in BigQuery
        // SELECT * FROM `project.billing.gcp_billing_export_v1_XXXX`
        // WHERE invoice.month = @month
        return [];
    }
}
