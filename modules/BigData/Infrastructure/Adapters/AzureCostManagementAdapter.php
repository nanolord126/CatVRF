<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Adapters;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Interfaces\CloudBillingAdapterInterface;

/**
 * Azure Cost Management Adapter
 *
 * Fetches billing data from Azure Cost Management API.
 */
final class AzureCostManagementAdapter implements CloudBillingAdapterInterface
{
    public function __construct(
        private readonly string $subscriptionId,
        private readonly string $tenantId,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {}

    public function getDailyBilling(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get("https://management.azure.com/subscriptions/{$this->subscriptionId}/providers/Microsoft.CostManagement/query", [
                    'api-version' => '2023-11-01',
                    'type' => 'ActualCost',
                    'timeframe' => 'Custom',
                    'timePeriod' => [
                        'from' => $startDate->format('Y-m-d'),
                        'to' => $endDate->format('Y-m-d'),
                    ],
                    'granularity' => 'Daily',
                    'groupings' => [
                        ['type' => 'Dimension', 'name' => 'ServiceName'],
                    ],
                ]);

            return $this->transformAzureResponse($response->json());
        } catch (\Throwable $e) {
            Log::error('Azure Cost Management: fetch failed', ['error' => $e->getMessage()]);
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
            $token = $this->getAccessToken();
            $response = Http::withToken($token)
                ->get("https://management.azure.com/subscriptions/{$this->subscriptionId}?api-version=2022-12-01");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function getAccessToken(): string
    {
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'resource' => 'https://management.azure.com/',
        ]);

        return $response->json('access_token', '');
    }

    private function transformAzureResponse(array $response): array
    {
        $records = [];
        $rows = $response['properties']['rows'] ?? [];

        foreach ($rows as $row) {
            $records[] = [
                'billing_id' => uniqid('azure_', true),
                'cloud_provider' => 'azure',
                'billing_date' => $row[1] ?? now()->toDateString(),
                'service' => $row[0] ?? 'unknown',
                'cost_category' => 'other',
                'environment' => 'production',
                'tenant_id' => 0,
                'seller_id' => 0,
                'workload' => '',
                'resource_tags' => '{}',
                'cost_amount' => (float) ($row[2] ?? 0),
                'cost_amount_rub' => (float) ($row[2] ?? 0) * (float) config('bigdata.cost.usd_to_rub', 95),
                'currency' => 'USD',
                'pricing_unit' => 'hours',
                'usage_quantity' => (float) ($row[3] ?? 0),
                'unit_price' => 0,
                'account_id' => $this->subscriptionId,
                'payment_type' => 'on_demand',
                'is_optimizable' => 0,
                'optimization_potential' => 0,
            ];
        }

        return $records;
    }
}
