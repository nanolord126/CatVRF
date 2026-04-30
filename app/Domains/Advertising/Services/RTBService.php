<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Domain\Entities\AdInventory;
use App\Domains\Advertising\Domain\Entities\Bid;
use App\Domains\Advertising\Domain\Interfaces\AdInventoryRepositoryInterface;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Real-Time Bidding (RTB) Service
 *
 * Handles RTB requests for programmatic ad buying.
 * Processes bid requests within 100ms SLA.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
final readonly class RTBService
{
    private const MAX_BID_RESPONSE_TIME_MS = 100;

    public function __construct(
        private readonly AdInventoryRepositoryInterface $inventoryRepository,
        private readonly FraudControlService $fraudService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Process RTB bid request
     * 
     * @param array $request RTB bid request payload
     * @param string $correlationId
     * @return array Bid response
     */
    public function processBidRequest(array $request, string $correlationId = ''): array
    {
        $startTime = microtime(true);
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('RTB bid request received', [
            'correlation_id' => $correlationId,
            'request_id' => $request['id'] ?? null,
        ]);

        try {
            // Extract request parameters
            $imp = $request['imp'][0] ?? [];
            $site = $request['site'] ?? [];
            $device = $request['device'] ?? [];
            $user = $request['user'] ?? [];

            $inventoryType = $imp['video']['placement'] ?? 'banner';
            $placement = $site['name'] ?? 'unknown';
            $userId = (int) ($user['id'] ?? 0);
            $ipAddress = $request['device']['ip'] ?? null;
            $deviceFingerprint = $request['device']['ua'] ?? null;

            // Fraud check
            $fraudResult = $this->fraudService->check(
                userId: $userId,
                operationType: 'rtb_bid',
                amount: 0,
                ipAddress: $ipAddress,
                deviceFingerprint: $deviceFingerprint,
                correlationId: $correlationId,
                context: ['request_id' => $request['id'] ?? null],
            );

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('RTB request blocked by fraud check', [
                    'correlation_id' => $correlationId,
                    'fraud_score' => $fraudResult['score'],
                ]);

                return $this->buildNoBidResponse('fraud_blocked');
            }

            // Find available inventory
            $inventory = $this->findAvailableInventory(
                inventoryType: $inventoryType,
                placement: $placement,
                targeting: $request['user'] ?? [],
            );

            if ($inventory === null) {
                $this->logger->debug('No available inventory for RTB request', [
                    'correlation_id' => $correlationId,
                    'inventory_type' => $inventoryType,
                    'placement' => $placement,
                ]);

                return $this->buildNoBidResponse('no_inventory');
            }

            // Calculate bid price based on inventory value and competition
            $bidPrice = $this->calculateBidPrice($inventory, $request);

            // Check SLA
            $elapsedMs = (microtime(true) - $startTime) * 1000;
            if ($elapsedMs > self::MAX_BID_RESPONSE_TIME_MS) {
                $this->logger->warning('RTB response exceeds SLA', [
                    'correlation_id' => $correlationId,
                    'elapsed_ms' => $elapsedMs,
                    'max_ms' => self::MAX_BID_RESPONSE_TIME_MS,
                ]);
            }

            $this->logger->info('RTB bid processed successfully', [
                'correlation_id' => $correlationId,
                'bid_price' => $bidPrice,
                'inventory_id' => $inventory->uuid,
                'elapsed_ms' => $elapsedMs,
            ]);

            return $this->buildBidResponse(
                requestId: $request['id'],
                bidPrice: $bidPrice,
                impressionId: $imp['id'],
                adMarkup: $this->generateAdMarkup($inventory),
                correlationId: $correlationId,
            );
        } catch (\Throwable $e) {
            $this->logger->error('RTB bid request processing failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->buildNoBidResponse('internal_error');
        }
    }

    /**
     * Process win notification
     */
    public function processWinNotification(array $notification, string $correlationId = ''): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('RTB win notification received', [
            'correlation_id' => $correlationId,
            'bid_id' => $notification['bid_id'] ?? null,
            'price' => $notification['price'] ?? null,
        ]);

        // Reserve inventory for the winning bid
        // TODO: Implement inventory reservation logic

        return true;
    }

    /**
     * Track impression from RTB
     */
    public function trackImpression(array $impressionData, string $correlationId = ''): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('RTB impression tracked', [
            'correlation_id' => $correlationId,
            'impression_id' => $impressionData['impression_id'] ?? null,
        ]);

        // Update inventory reserved impressions
        // TODO: Implement impression tracking logic

        return true;
    }

    /**
     * Track click from RTB
     */
    public function trackClick(array $clickData, string $correlationId = ''): bool
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->logger->info('RTB click tracked', [
            'correlation_id' => $correlationId,
            'click_id' => $clickData['click_id'] ?? null,
        ]);

        // Record click in analytics
        // TODO: Implement click tracking logic

        return true;
    }

    private function findAvailableInventory(
        string $inventoryType,
        string $placement,
        array $targeting,
    ): ?AdInventory {
        // Find available inventory matching criteria
        $inventories = $this->inventoryRepository->findAvailable(
            inventoryType: $inventoryType,
            placement: $placement,
            targeting: $targeting,
        );

        return $inventories->first() ?? null;
    }

    private function calculateBidPrice(AdInventory $inventory, array $request): int
    {
        // Base price based on inventory type and placement
        $basePrice = match ($inventory->inventory_type) {
            'short' => 50000, // 500 RUB per 1000 impressions
            'video' => 80000,
            'banner' => 30000,
            'native' => 40000,
            default => 30000,
        };

        // Adjust based on placement
        $placementMultiplier = match ($inventory->placement) {
            'feed' => 1.2,
            'story' => 1.5,
            'search' => 2.0,
            default => 1.0,
        };

        // Adjust based on competition (inventory scarcity)
        $scarcityMultiplier = 1 + (1 - $inventory->getRemainingImpressions() / $inventory->available_impressions) * 0.5;

        $finalPrice = (int) ($basePrice * $placementMultiplier * $scarcityMultiplier);

        return $finalPrice;
    }

    private function buildBidResponse(
        string $requestId,
        int $bidPrice,
        string $impressionId,
        string $adMarkup,
        string $correlationId,
    ): array {
        return [
            'id' => $requestId,
            'seatbid' => [
                [
                    'bid' => [
                        [
                            'id' => (string) Str::uuid(),
                            'impid' => $impressionId,
                            'price' => $bidPrice,
                            'adid' => (string) Str::uuid(),
                            'nurl' => route('api.rtb.win-notification'),
                            'adm' => $adMarkup,
                            'ext' => [
                                'correlation_id' => $correlationId,
                            ],
                        ],
                    ],
                ],
            ],
            'cur' => 'RUB',
            'ext' => [
                'processing_time_ms' => (int) ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000),
            ],
        ];
    }

    private function buildNoBidResponse(string $reason): array
    {
        return [
            'id' => null,
            'seatbid' => [],
            'nbr' => $reason,
            'cur' => 'RUB',
        ];
    }

    private function generateAdMarkup(AdInventory $inventory): string
    {
        // Generate ad markup based on inventory type
        return match ($inventory->inventory_type) {
            'short' => $this->generateShortAdMarkup($inventory),
            'video' => $this->generateVideoAdMarkup($inventory),
            'banner' => $this->generateBannerAdMarkup($inventory),
            'native' => $this->generateNativeAdMarkup($inventory),
            default => '<div>Ad</div>',
        };
    }

    private function generateShortAdMarkup(AdInventory $inventory): string
    {
        return json_encode([
            'type' => 'short',
            'video_url' => 'https://cdn.example.com/ads/short.mp4',
            'duration' => 15,
            'call_to_action' => 'Learn More',
            'tracking_url' => route('api.rtb.impression'),
            'click_url' => route('api.rtb.click'),
        ]);
    }

    private function generateVideoAdMarkup(AdInventory $inventory): string
    {
        return json_encode([
            'type' => 'video',
            'video_url' => 'https://cdn.example.com/ads/video.mp4',
            'duration' => 30,
            'skip_after' => 5,
            'tracking_url' => route('api.rtb.impression'),
            'click_url' => route('api.rtb.click'),
        ]);
    }

    private function generateBannerAdMarkup(AdInventory $inventory): string
    {
        return json_encode([
            'type' => 'banner',
            'image_url' => 'https://cdn.example.com/ads/banner.jpg',
            'width' => 300,
            'height' => 250,
            'tracking_pixel' => route('api.rtb.impression'),
            'click_url' => route('api.rtb.click'),
        ]);
    }

    private function generateNativeAdMarkup(AdInventory $inventory): string
    {
        return json_encode([
            'type' => 'native',
            'title' => 'Native Ad Title',
            'description' => 'Ad description text',
            'image_url' => 'https://cdn.example.com/ads/native.jpg',
            'icon_url' => 'https://cdn.example.com/ads/icon.png',
            'cta' => 'Install',
            'tracking_pixel' => route('api.rtb.impression'),
            'click_url' => route('api.rtb.click'),
        ]);
    }
}
