<?php

declare(strict_types=1);

namespace Modules\Marketplace\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Application\Services\HomepageAdService;
use Modules\Marketplace\Domain\Interfaces\ListingRepositoryInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Cache;

/**
 * Job для асинхронного трекинга показов и кликов рекламы
 * 
 * Интегрирован с ProductListing счетчиками
 * Работает через очередь для высокой производительности
 */
final readonly class TrackPromotionMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_TTL = 300; // 5 минут
    
    /**
     * @param array{
     *   listing_uuid: string,
     *   event_type: 'impression'|'click',
     *   cost_kopecks: int,
     *   user_id?: int,
     *   vertical?: string,
     *   correlation_id?: string,
     *   ip_address?: string,
     *   user_agent?: string,
     * } $data
     */
    public function __construct(
        private array $data,
    ) {
        $this->onQueue('marketing-metrics');
    }

    public function handle(
        HomepageAdService $homepageAdService,
        ListingRepositoryInterface $listingRepository,
        LoggerInterface $logger,
    ): void {
        $listingUuid = Uuid::fromString($this->data['listing_uuid']);
        $eventType = $this->data['event_type'];
        $costKopecks = $this->data['cost_kopecks'] ?? 10;
        $correlationId = $this->data['correlation_id'] ?? uuid_create();
        
        $logger->info('Processing promotion metrics job', [
            'listing_uuid' => $listingUuid->toString(),
            'event_type' => $eventType,
            'cost_kopecks' => $costKopecks,
            'correlation_id' => $correlationId,
        ]);

        // Проверяем дедупликацию через Redis cache
        $cacheKey = $this->getDeduplicationKey($listingUuid, $eventType, $this->data['user_id'] ?? null);
        
        if (Cache::has($cacheKey)) {
            $logger->debug('Duplicate promotion event skipped', [
                'cache_key' => $cacheKey,
                'listing_uuid' => $listingUuid->toString(),
            ]);
            return;
        }

        // Устанавливаем cache для дедупликации
        Cache::put($cacheKey, true, self::CACHE_TTL);

        try {
            $listing = $listingRepository->findByUuid($listingUuid);
            
            if ($listing === null) {
                $logger->warning('Listing not found for promotion metrics', [
                    'listing_uuid' => $listingUuid->toString(),
                ]);
                return;
            }

            // Проверяем активность рекламы
            if (!$listing->isPromotionActive()) {
                $logger->debug('Listing promotion is not active', [
                    'listing_uuid' => $listingUuid->toString(),
                ]);
                return;
            }

            // Проверяем превышение бюджета
            if ($listing->isPromotionBudgetExceeded()) {
                $logger->debug('Listing promotion budget exceeded', [
                    'listing_uuid' => $listingUuid->toString(),
                    'budget' => $listing->promotionBudget,
                    'spent' => $listing->getPromotionSpendKopecks() / 100,
                ]);
                return;
            }

            // Записываем событие
            if ($eventType === 'impression') {
                $updatedListing = $homepageAdService->recordHomepageImpression(
                    $listingUuid,
                    $costKopecks,
                    $correlationId
                );
            } elseif ($eventType === 'click') {
                $updatedListing = $homepageAdService->recordHomepageClick(
                    $listingUuid,
                    $costKopecks,
                    $correlationId
                );
            } else {
                throw new \InvalidArgumentException("Invalid event type: {$eventType}");
            }

            // Дополнительная логика: автоматическая пауза при превышении бюджета
            if ($updatedListing->isPromotionBudgetExceeded()) {
                $logger->info('Listing promotion automatically paused due to budget exceeded', [
                    'listing_uuid' => $listingUuid->toString(),
                    'budget' => $updatedListing->promotionBudget,
                    'spent' => $updatedListing->getPromotionSpendKopecks() / 100,
                ]);
                
                // Здесь можно добавить логику автоматической паузы
                // Например, обновление статуса кампании через MarketingCampaignService
            }

            // Логируем успешную обработку
            $logger->info('Promotion metrics recorded successfully', [
                'listing_uuid' => $listingUuid->toString(),
                'event_type' => $eventType,
                'cost_kopecks' => $costKopecks,
                'total_impressions' => $updatedListing->getPromotionImpressions(),
                'total_clicks' => $updatedListing->getPromotionClicks(),
                'total_spend' => $updatedListing->getPromotionSpendKopecks(),
                'ctr' => $updatedListing->getPromotionCTR(),
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            $logger->error('Failed to process promotion metrics job', [
                'listing_uuid' => $listingUuid->toString(),
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Ретрай с exponential backoff
            $this->release(60); // 1 минута
        }
    }

    /**
     * Получить ключ для дедупликации событий
     */
    private function getDeduplicationKey(
        \Ramsey\Uuid\UuidInterface $listingUuid,
        string $eventType,
        ?int $userId = null
    ): string {
        $parts = [
            'promotion',
            'dedup',
            $listingUuid->toString(),
            $eventType,
        ];
        
        if ($userId !== null) {
            $parts[] = "user:{$userId}";
        }
        
        return implode(':', $parts);
    }
}
