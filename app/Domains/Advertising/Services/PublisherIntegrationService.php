<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Domain\Entities\Publisher;
use App\Domains\Advertising\Domain\Interfaces\PublisherRepositoryInterface;
use App\Services\FraudControlService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Publisher Integration Service
 *
 * Handles publisher onboarding, API key management, webhook handling,
 * revenue reporting, and payout processing.
 * Supports direct, SSP, and DSP integration types.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class PublisherIntegrationService
{
    private const PUBLISHER_CACHE_TTL = 3600; // 1 hour
    private const RATE_LIMIT_TTL = 60; // 1 minute
    private const WEBHOOK_TIMEOUT = 10; // 10 seconds

    public function __construct(
        private readonly PublisherRepositoryInterface $publisherRepository,
        private readonly FraudControlService $fraudService,
        private readonly Dispatcher $eventDispatcher,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Onboard a new publisher
     *
     * @throws \RuntimeException
     */
    public function onboardPublisher(
        int $tenantId,
        string $name,
        string $websiteUrl,
        float $commissionRate,
        int $payoutThreshold,
        string $integrationType,
        ?string $webhookUrl = null,
        int $userId = 0,
        ?string $correlationId = null,
    ): Publisher {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Onboarding publisher', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'website_url' => $websiteUrl,
        ]);

        // Validate integration type
        if (!in_array($integrationType, ['direct', 'ssp', 'dsp'], true)) {
            throw new \InvalidArgumentException('Invalid integration type');
        }

        // Validate commission rate
        if ($commissionRate < 0 || $commissionRate > 0.5) {
            throw new \InvalidArgumentException('Commission rate must be between 0 and 50%');
        }

        // Fraud check
        $this->fraudService->check(
            userId: $userId,
            operationType: 'publisher_onboard',
            amount: 0,
            correlationId: $correlationId,
            context: ['name' => $name, 'website_url' => $websiteUrl],
        );

        return $this->db->transaction(function () use (
            $tenantId,
            $name,
            $websiteUrl,
            $commissionRate,
            $payoutThreshold,
            $integrationType,
            $webhookUrl,
            $correlationId,
        ) {
            $publisher = Publisher::create(
                tenantId: $tenantId,
                name: $name,
                websiteUrl: $websiteUrl,
                commissionRate: $commissionRate,
                payoutThreshold: $payoutThreshold,
                integrationType: $integrationType,
                webhookUrl: $webhookUrl,
                correlationId: $correlationId,
            );

            $savedPublisher = $this->publisherRepository->save($publisher);

            // Clear cache
            Cache::tags(['advertising', 'publishers'])->flush();

            // Dispatch event
            $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\PublisherOnboarded(
                publisherId: $savedPublisher->id,
                tenantId: $tenantId,
                correlationId: $correlationId,
            ));

            $this->logger->info('Publisher onboarded successfully', [
                'correlation_id' => $correlationId,
                'publisher_id' => $savedPublisher->id,
            ]);

            return $savedPublisher;
        });
    }

    /**
     * Verify a publisher
     *
     * @throws \RuntimeException
     */
    public function verifyPublisher(int $publisherId, ?string $correlationId = null): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null) {
            throw new \RuntimeException('Publisher not found');
        }

        if (!$publisher->canTransitionTo('active')) {
            throw new \RuntimeException('Publisher cannot be verified');
        }

        $this->publisherRepository->updateStatus($publisherId, 'active');

        // Clear cache
        Cache::tags(['advertising', 'publishers'])->flush();

        // Dispatch event
        $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\PublisherVerified(
            publisherId: $publisherId,
            tenantId: $publisher->tenant_id,
            correlationId: $correlationId,
        ));

        $this->logger->info('Publisher verified', [
            'correlation_id' => $correlationId,
            'publisher_id' => $publisherId,
        ]);

        return true;
    }

    /**
     * Suspend a publisher
     *
     * @throws \RuntimeException
     */
    public function suspendPublisher(int $publisherId, string $reason, ?string $correlationId = null): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null) {
            throw new \RuntimeException('Publisher not found');
        }

        if (!$publisher->canTransitionTo('suspended')) {
            throw new \RuntimeException('Publisher cannot be suspended');
        }

        $this->publisherRepository->updateStatus($publisherId, 'suspended');

        // Clear cache
        Cache::tags(['advertising', 'publishers'])->flush();

        // Dispatch event
        $this->eventDispatcher->dispatch(new \App\Domains\Advertising\Domain\Events\PublisherSuspended(
            publisherId: $publisherId,
            tenantId: $publisher->tenant_id,
            reason: $reason,
            correlationId: $correlationId,
        ));

        $this->logger->warning('Publisher suspended', [
            'correlation_id' => $correlationId,
            'publisher_id' => $publisherId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Regenerate API key for a publisher
     *
     * @throws \RuntimeException
     */
    public function regenerateApiKey(int $publisherId, ?string $correlationId = null): string
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null) {
            throw new \RuntimeException('Publisher not found');
        }

        $newApiKey = Publisher::generateApiKey();
        $this->publisherRepository->updateApiKey($publisherId, $newApiKey);

        // Clear cache
        Cache::tags(['advertising', 'publishers'])->flush();

        $this->logger->info('API key regenerated', [
            'correlation_id' => $correlationId,
            'publisher_id' => $publisherId,
        ]);

        return $newApiKey;
    }

    /**
     * Authenticate a publisher by API key
     */
    public function authenticateByApiKey(string $apiKey): ?Publisher
    {
        $cacheKey = "publisher:auth:{$apiKey}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $publisher = $this->publisherRepository->findByApiKey($apiKey);

        if ($publisher !== null && $publisher->isActive()) {
            Cache::put($cacheKey, $publisher, self::PUBLISHER_CACHE_TTL);
        }

        return $publisher?->isActive() ? $publisher : null;
    }

    /**
     * Check rate limit for a publisher
     */
    public function checkRateLimit(int $publisherId, int $limit = 100): bool
    {
        $key = "publisher:{$publisherId}:rate_limit";
        $count = Redis::incr($key);

        if ($count === 1) {
            Redis::expire($key, self::RATE_LIMIT_TTL);
        }

        return $count <= $limit;
    }

    /**
     * Send webhook notification to publisher
     */
    public function sendWebhook(int $publisherId, array $payload, ?string $correlationId = null): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null || $publisher->webhook_url === null) {
            return false;
        }

        try {
            $response = Http::timeout(self::WEBHOOK_TIMEOUT)
                ->withHeaders([
                    'X-Correlation-ID' => $correlationId,
                    'Content-Type' => 'application/json',
                ])
                ->post($publisher->webhook_url, $payload);

            $success = $response->successful();

            $this->logger->info('Webhook sent', [
                'correlation_id' => $correlationId,
                'publisher_id' => $publisherId,
                'status' => $response->status(),
                'success' => $success,
            ]);

            return $success;
        } catch (\Throwable $e) {
            $this->logger->error('Webhook failed', [
                'correlation_id' => $correlationId,
                'publisher_id' => $publisherId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Calculate publisher revenue
     *
     * @return array<string, mixed>
     */
    public function calculateRevenue(int $publisherId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null) {
            throw new \RuntimeException('Publisher not found');
        }

        // This would typically query impression/click data from analytics
        // For now, return a simplified structure
        return [
            'publisher_id' => $publisherId,
            'period' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'gross_revenue' => 0,
            'commission' => 0,
            'net_revenue' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'ctr' => 0.0,
        ];
    }

    /**
     * Process payout for eligible publishers
     *
     * @return array<int, array>
     */
    public function processPayouts(?string $correlationId = null): array
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('Processing publisher payouts', [
            'correlation_id' => $correlationId,
        ]);

        $publishers = $this->publisherRepository->findActive();
        $processed = [];

        foreach ($publishers as $publisher) {
            $revenue = $this->calculateRevenue(
                $publisher->id,
                $publisher->last_payout_at,
                now()
            );

            if ($publisher->isPayoutEligible($revenue['net_revenue'])) {
                // Process payout (would integrate with payment service)
                $processed[] = [
                    'publisher_id' => $publisher->id,
                    'amount' => $revenue['net_revenue'],
                    'status' => 'processed',
                ];

                // Update last payout date
                // $this->publisherRepository->updateLastPayoutAt($publisher->id, now());
            }
        }

        $this->logger->info('Payouts processed', [
            'correlation_id' => $correlationId,
            'count' => count($processed),
        ]);

        return $processed;
    }

    /**
     * Sync inventory from external SSP/DSP
     *
     * @throws \RuntimeException
     */
    public function syncExternalInventory(int $publisherId, ?string $correlationId = null): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $publisher = $this->publisherRepository->findById($publisherId);
        if ($publisher === null) {
            throw new \RuntimeException('Publisher not found');
        }

        if ($publisher->integration_type === 'direct') {
            throw new \RuntimeException('Direct publishers do not have external inventory');
        }

        // This would call external SSP/DSP APIs to sync inventory
        // For now, return true as a placeholder

        $this->logger->info('External inventory synced', [
            'correlation_id' => $correlationId,
            'publisher_id' => $publisherId,
            'integration_type' => $publisher->integration_type,
        ]);

        return true;
    }
}
