<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Domains\RealEstate\Models\ViewingAppointment;
use App\Domains\RealEstate\Domain\Enums\PropertyStatusEnum;
use App\Domains\RealEstate\Domain\Enums\TransactionStatusEnum;
use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

final readonly class RealEstateCRMIntegrationService
{
    private const CRM_API_TIMEOUT_SECONDS = 30;
    private const CRM_RETRY_ATTEMPTS = 3;
    private const CRM_RETRY_DELAY_MS = 1000;
    private const CRM_CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
    ) {}

    public function syncPropertyToCRM(Property $property, string $event, string $correlationId): array
    {
        $this->fraud->check(
            userId: $property->seller_id,
            operationType: 'crm_sync_property',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "crm:property:{$property->uuid}:{$event}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $payload = $this->buildPropertyPayload($property, $event);

        $response = $this->sendToCRM('properties', 'POST', $payload, $correlationId);

        Cache::put($cacheKey, $response, self::CRM_CACHE_TTL_SECONDS);

        Log::channel('audit')->info('Property synced to CRM', [
            'property_id' => $property->id,
            'property_uuid' => $property->uuid,
            'event' => $event,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function syncTransactionToCRM(PropertyTransaction $transaction, string $event, string $correlationId): array
    {
        $this->fraud->check(
            userId: $transaction->buyer_id,
            operationType: 'crm_sync_transaction',
            amount: (int) $transaction->amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "crm:transaction:{$transaction->uuid}:{$event}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $payload = $this->buildTransactionPayload($transaction, $event);

        $response = $this->sendToCRM('transactions', 'POST', $payload, $correlationId);

        Cache::put($cacheKey, $response, self::CRM_CACHE_TTL_SECONDS);

        Log::channel('audit')->info('Transaction synced to CRM', [
            'transaction_id' => $transaction->id,
            'transaction_uuid' => $transaction->uuid,
            'event' => $event,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function syncViewingToCRM(ViewingAppointment $viewing, string $event, string $correlationId): array
    {
        $this->fraud->check(
            userId: $viewing->buyer_id,
            operationType: 'crm_sync_viewing',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "crm:viewing:{$viewing->uuid}:{$event}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $payload = $this->buildViewingPayload($viewing, $event);

        $response = $this->sendToCRM('viewings', 'POST', $payload, $correlationId);

        Cache::put($cacheKey, $response, self::CRM_CACHE_TTL_SECONDS);

        Log::channel('audit')->info('Viewing synced to CRM', [
            'viewing_id' => $viewing->id,
            'viewing_uuid' => $viewing->uuid,
            'event' => $event,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function syncLeadToCRM(int $propertyId, int $buyerId, string $leadSource, string $correlationId): array
    {
        $this->fraud->check(
            userId: $buyerId,
            operationType: 'crm_sync_lead',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $property = Property::findOrFail($propertyId);

        $payload = [
            'lead_id' => Str::uuid()->toString(),
            'property_uuid' => $property->uuid,
            'property_title' => $property->title,
            'property_price' => $property->price,
            'buyer_id' => $buyerId,
            'lead_source' => $leadSource,
            'lead_status' => 'new',
            'created_at' => now()->toIso8601String(),
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'tenant_id' => $property->tenant_id,
            ],
        ];

        $response = $this->sendToCRM('leads', 'POST', $payload, $correlationId);

        Log::channel('audit')->info('Lead synced to CRM', [
            'property_id' => $propertyId,
            'buyer_id' => $buyerId,
            'lead_source' => $leadSource,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function updateCRMDealStatus(string $crmDealId, string $status, string $correlationId): array
    {
        $payload = [
            'deal_id' => $crmDealId,
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ];

        $response = $this->sendToCRM("deals/{$crmDealId}", 'PATCH', $payload, $correlationId);

        Log::channel('audit')->info('CRM deal status updated', [
            'deal_id' => $crmDealId,
            'status' => $status,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function getCRMDealHistory(string $crmDealId, string $correlationId): array
    {
        $cacheKey = "crm:deal:{$crmDealId}:history";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $response = $this->sendToCRM("deals/{$crmDealId}/history", 'GET', [], $correlationId);

        Cache::put($cacheKey, $response, self::CRM_CACHE_TTL_SECONDS);

        return $response;
    }

    public function createCRMDealFromTransaction(PropertyTransaction $transaction, string $correlationId): array
    {
        $this->fraud->check(
            userId: $transaction->buyer_id,
            operationType: 'crm_create_deal',
            amount: (int) $transaction->amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $property = $transaction->property;
        $buyer = $transaction->buyer;
        $seller = $transaction->seller;

        $payload = [
            'deal_id' => Str::uuid()->toString(),
            'transaction_uuid' => $transaction->uuid,
            'property_uuid' => $property->uuid,
            'property_title' => $property->title,
            'property_address' => $property->address,
            'deal_amount' => $transaction->amount,
            'deal_currency' => $transaction->currency,
            'buyer_id' => $transaction->buyer_id,
            'buyer_name' => $buyer->name ?? 'Unknown',
            'buyer_email' => $buyer->email,
            'buyer_phone' => $buyer->phone ?? '',
            'seller_id' => $transaction->seller_id,
            'seller_name' => $seller->name ?? 'Unknown',
            'agent_id' => $transaction->agent_id,
            'agent_name' => $transaction->agent?->name ?? '',
            'deal_status' => $this->mapTransactionStatusToCRM($transaction->status),
            'is_b2b' => $transaction->is_b2b,
            'commission_rate' => $transaction->commission_rate,
            'commission_amount' => $transaction->commission_amount,
            'created_at' => $transaction->created_at->toIso8601String(),
            'metadata' => [
                'tenant_id' => $transaction->tenant_id,
                'correlation_id' => $transaction->correlation_id,
                'split_config' => $transaction->split_config,
            ],
        ];

        $response = $this->sendToCRM('deals', 'POST', $payload, $correlationId);

        Log::channel('audit')->info('CRM deal created from transaction', [
            'transaction_id' => $transaction->id,
            'transaction_uuid' => $transaction->uuid,
            'deal_id' => $response['deal_id'] ?? null,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function syncAgentActivityToCRM(int $agentId, string $activityType, array $activityData, string $correlationId): array
    {
        $payload = [
            'activity_id' => Str::uuid()->toString(),
            'agent_id' => $agentId,
            'activity_type' => $activityType,
            'activity_data' => $activityData,
            'created_at' => now()->toIso8601String(),
        ];

        $response = $this->sendToCRM('agent-activities', 'POST', $payload, $correlationId);

        Log::channel('audit')->info('Agent activity synced to CRM', [
            'agent_id' => $agentId,
            'activity_type' => $activityType,
            'crm_response' => $response,
            'correlation_id' => $correlationId,
        ]);

        return $response;
    }

    public function getCRMAnalytics(int $tenantId, string $startDate, string $endDate, string $correlationId): array
    {
        $cacheKey = "crm:analytics:{$tenantId}:{$startDate}:{$endDate}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $response = $this->sendToCRM('analytics', 'GET', [
            'tenant_id' => $tenantId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], $correlationId);

        Cache::put($cacheKey, $response, self::CRM_CACHE_TTL_SECONDS);

        return $response;
    }

    private function buildPropertyPayload(Property $property, string $event): array
    {
        return [
            'property_uuid' => $property->uuid,
            'event' => $event,
            'title' => $property->title,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'listing_type' => $property->listing_type,
            'price' => $property->price,
            'currency' => $property->currency,
            'area' => $property->area,
            'rooms' => $property->rooms,
            'address' => $property->address,
            'city' => $property->city,
            'district' => $property->district,
            'lat' => $property->lat,
            'lon' => $property->lon,
            'status' => $property->status,
            'seller_id' => $property->seller_id,
            'agent_id' => $property->agent_id,
            'is_b2b' => $property->is_b2b,
            'is_featured' => $property->is_featured,
            'is_verified' => $property->is_verified,
            'blockchain_verified' => $property->blockchain_verified,
            'liquidity_score' => $property->liquidity_score,
            'fraud_score' => $property->fraud_score,
            'suggested_price' => $property->suggested_price,
            'published_at' => $property->published_at?->toIso8601String(),
            'sold_at' => $property->sold_at?->toIso8601String(),
            'archived_at' => $property->archived_at?->toIso8601String(),
            'metadata' => $property->metadata,
            'tags' => $property->tags,
            'event_timestamp' => now()->toIso8601String(),
        ];
    }

    private function buildTransactionPayload(PropertyTransaction $transaction, string $event): array
    {
        return [
            'transaction_uuid' => $transaction->uuid,
            'event' => $event,
            'property_uuid' => $transaction->property->uuid,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'buyer_id' => $transaction->buyer_id,
            'seller_id' => $transaction->seller_id,
            'agent_id' => $transaction->agent_id,
            'is_b2b' => $transaction->is_b2b,
            'commission_rate' => $transaction->commission_rate,
            'commission_amount' => $transaction->commission_amount,
            'split_config' => $transaction->split_config,
            'escrow_hold_until' => $transaction->escrow_hold_until?->toIso8601String(),
            'released_at' => $transaction->released_at?->toIso8601String(),
            'refunded_at' => $transaction->refunded_at?->toIso8601String(),
            'release_reason' => $transaction->release_reason,
            'refund_reason' => $transaction->refund_reason,
            'metadata' => $transaction->metadata,
            'tags' => $transaction->tags,
            'event_timestamp' => now()->toIso8601String(),
        ];
    }

    private function buildViewingPayload(ViewingAppointment $viewing, string $event): array
    {
        return [
            'viewing_uuid' => $viewing->uuid,
            'event' => $event,
            'property_uuid' => $viewing->property->uuid,
            'property_title' => $viewing->property->title,
            'property_address' => $viewing->property->address,
            'buyer_id' => $viewing->buyer_id,
            'agent_id' => $viewing->agent_id,
            'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
            'duration_minutes' => $viewing->duration_minutes,
            'status' => $viewing->status,
            'is_b2b' => $viewing->is_b2b,
            'faceid_verified' => $viewing->faceid_verified_at !== null,
            'faceid_verified_at' => $viewing->faceid_verified_at?->toIso8601String(),
            'faceid_confidence_score' => $viewing->faceid_confidence_score,
            'contact_phone' => $viewing->contact_phone,
            'contact_email' => $viewing->contact_email,
            'special_requests' => $viewing->special_requests,
            'started_at' => $viewing->started_at?->toIso8601String(),
            'completed_at' => $viewing->completed_at?->toIso8601String(),
            'cancelled_at' => $viewing->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $viewing->cancellation_reason,
            'metadata' => $viewing->metadata,
            'tags' => $viewing->tags,
            'event_timestamp' => now()->toIso8601String(),
        ];
    }

    private function mapTransactionStatusToCRM(string $transactionStatus): string
    {
        return match ($transactionStatus) {
            TransactionStatusEnum::ESCROW_PENDING->value => 'negotiation',
            TransactionStatusEnum::ESCROW_RELEASED->value => 'deposit_received',
            TransactionStatusEnum::ESCROW_REFUNDED->value => 'cancelled',
            TransactionStatusEnum::PAYMENT_PENDING->value => 'payment_pending',
            TransactionStatusEnum::PAYMENT_COMPLETED->value => 'payment_completed',
            TransactionStatusEnum::PAYMENT_FAILED->value => 'payment_failed',
            TransactionStatusEnum::COMPLETED->value => 'closed',
            TransactionStatusEnum::CANCELLED->value => 'cancelled',
            default => 'new',
        };
    }

    private function sendToCRM(string $endpoint, string $method, array $data, string $correlationId): array
    {
        $crmUrl = config('services.crm.url', 'https://api.crm.example.com/v1');
        $crmApiKey = config('services.crm.api_key');

        if (empty($crmApiKey)) {
            Log::warning('CRM API key not configured', ['correlation_id' => $correlationId]);
            return ['success' => false, 'error' => 'CRM not configured'];
        }

        $url = rtrim($crmUrl, '/') . '/' . ltrim($endpoint, '/');

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::CRM_RETRY_ATTEMPTS) {
            try {
                $response = Http::timeout(self::CRM_API_TIMEOUT_SECONDS)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $crmApiKey,
                        'Content-Type' => 'application/json',
                        'X-Correlation-ID' => $correlationId,
                        'X-Tenant-ID' => tenant()->id,
                        'User-Agent' => 'CatVRF-RealEstate/1.0',
                    ])
                    ->send($method, $url, $method === 'GET' ? ['query' => $data] : ['json' => $data]);

                if ($response->successful()) {
                    return $response->json();
                }

                $errorData = $response->json();
                Log::error('CRM API error', [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'status' => $response->status(),
                    'response' => $errorData,
                    'correlation_id' => $correlationId,
                ]);

                if ($response->status() >= 400 && $response->status() < 500) {
                    return [
                        'success' => false,
                        'error' => $errorData['error'] ?? 'CRM API error',
                        'status' => $response->status(),
                    ];
                }

                $lastException = new Exception('CRM API error: ' . $response->body());
            } catch (Exception $e) {
                $lastException = $e;
                Log::warning('CRM API request failed', [
                    'endpoint' => $endpoint,
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }

            $attempt++;
            if ($attempt < self::CRM_RETRY_ATTEMPTS) {
                usleep(self::CRM_RETRY_DELAY_MS * 1000);
            }
        }

        Log::error('CRM API request failed after retries', [
            'endpoint' => $endpoint,
            'method' => $method,
            'attempts' => $attempt,
            'error' => $lastException->getMessage(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => false,
            'error' => $lastException->getMessage(),
            'attempts' => $attempt,
        ];
    }
}
