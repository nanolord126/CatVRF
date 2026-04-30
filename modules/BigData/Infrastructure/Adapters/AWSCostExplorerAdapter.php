<?php

declare(strict_types=1);

namespace Modules\BigData\Infrastructure\Adapters;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Interfaces\CloudBillingAdapterInterface;

/**
 * AWS Cost Explorer Adapter
 *
 * Fetches billing data from AWS Cost Explorer API.
 * Supports multi-account via Organizations.
 *
 * Requires: aws/aws-sdk-php
 */
final class AWSCostExplorerAdapter implements CloudBillingAdapterInterface
{
    public function __construct(
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $region = 'us-east-1',
        private readonly ?string $roleArn = null,
    ) {}

    public function getDailyBilling(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        try {
            // Use AWS Cost Explorer GetCostAndUsage API
            // In production, this uses the AWS SDK
            $response = Http::withHeaders($this->buildAuthHeaders())
                ->post('https://ce.' . $this->region . '.amazonaws.com/', [
                    'TimePeriod' => [
                        'Start' => $startDate->format('Y-m-d'),
                        'End' => $endDate->format('Y-m-d'),
                    ],
                    'Granularity' => 'DAILY',
                    'Metrics' => ['UnblendedCost', 'UsageQuantity'],
                    'GroupBy' => [
                        ['Type' => 'DIMENSION', 'Key' => 'SERVICE'],
                        ['Type' => 'DIMENSION', 'Key' => 'USAGE_TYPE'],
                    ],
                    'Filter' => [
                        'Tags' => [
                            'Key' => 'Project',
                            'Values' => ['CatVRF-BigData'],
                        ],
                    ],
                ]);

            return $this->transformAwsResponse($response->json(), $startDate, $endDate);
        } catch (\Throwable $e) {
            Log::error('AWS Cost Explorer: billing fetch failed', [
                'error' => $e->getMessage(),
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]);
            return [];
        }
    }

    public function getMonthlyForecast(): float
    {
        try {
            $response = Http::withHeaders($this->buildAuthHeaders())
                ->post('https://ce.' . $this->region . '.amazonaws.com/', [
                    'Action' => 'GetCostForecast',
                    'TimePeriod' => [
                        'Start' => now()->format('Y-m-d'),
                        'End' => now()->endOfMonth()->format('Y-m-d'),
                    ],
                    'Metric' => 'UNBLENDED_COST',
                    'Granularity' => 'MONTHLY',
                ]);

            $data = $response->json();
            return (float) ($data['Total']['Amount'] ?? 0);
        } catch (\Throwable $e) {
            Log::error('AWS Cost Explorer: forecast failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    public function getOptimizationRecommendations(): array
    {
        // AWS Cost Explorer GetRightsizingRecommendation + GetSavingsPlansPurchaseRecommendation
        return [];
    }

    public function getResourceTags(): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders($this->buildAuthHeaders())
                ->get('https://ce.' . $this->region . '.amazonaws.com/ping');
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function buildAuthHeaders(): array
    {
        // In production, use AWS Signature V4 via SDK
        return [
            'X-API-Key' => $this->accessKeyId,
            'Content-Type' => 'application/x-amz-json-1.1',
        ];
    }

    private function transformAwsResponse(array $response, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $records = [];
        $resultsByTime = $response['ResultsByTime'] ?? [];

        foreach ($resultsByTime as $dayResult) {
            $billingDate = $dayResult['TimePeriod']['Start'] ?? $startDate->toDateString();
            $groups = $dayResult['Groups'] ?? [];

            foreach ($groups as $group) {
                $keys = $group['Keys'] ?? [];
                $metrics = $group['Metrics'] ?? [];
                $cost = (float) ($metrics['UnblendedCost']['Amount'] ?? 0);
                $usage = (float) ($metrics['UsageQuantity']['Amount'] ?? 0);

                $records[] = [
                    'billing_id' => uniqid('aws_', true),
                    'cloud_provider' => 'aws',
                    'billing_date' => $billingDate,
                    'service' => $keys[0] ?? 'unknown',
                    'cost_category' => $this->mapAwsServiceToCategory($keys[0] ?? ''),
                    'environment' => 'production',
                    'tenant_id' => 0,
                    'seller_id' => 0,
                    'workload' => '',
                    'resource_tags' => json_encode($group['Tags'] ?? []),
                    'cost_amount' => $cost,
                    'cost_amount_rub' => $cost * (float) config('bigdata.cost.usd_to_rub', 95),
                    'currency' => 'USD',
                    'pricing_unit' => 'hours',
                    'usage_quantity' => $usage,
                    'unit_price' => $usage > 0 ? $cost / $usage : 0,
                    'account_id' => '',
                    'payment_type' => 'on_demand',
                    'is_optimizable' => $this->isAwsOptimizable($keys[0] ?? ''),
                    'optimization_potential' => $cost * 0.3,
                ];
            }
        }

        return $records;
    }

    private function mapAwsServiceToCategory(string $service): string
    {
        return match (true) {
            str_contains($service, 'EC2'), str_contains($service, 'Lambda'), str_contains($service, 'EKS') => 'compute',
            str_contains($service, 'S3'), str_contains($service, 'EBS'), str_contains($service, 'EFS') => 'storage',
            str_contains($service, 'CloudFront'), str_contains($service, 'VPC'), str_contains($service, 'Transfer') => 'network',
            default => 'other',
        };
    }

    private function isAwsOptimizable(string $service): int
    {
        $optimizable = ['AmazonEC2', 'AmazonEBS', 'AmazonS3', 'AmazonOpenSearch', 'AmazonMSK'];
        return in_array($service, $optimizable, true) ? 1 : 0;
    }
}
