<?php declare(strict_types=1);

namespace App\Services\Marketing;


use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\FraudControl\FraudControlService;
use App\Services\ML\AnonymizationService;
use App\Services\ML\BigDataAggregatorService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * AdEngineService — рекламный движок.
 *
 * Правила канона:
 *  - Таргетинг строится только на обезличенных данных + UserTasteProfile
 *  - Трекинг impressions/clicks — через BigDataAggregatorService (ClickHouse)
 *  - Шортсы генерируются через ShortVideoAdService
 *  - Fraud-check и rate-limit на все рекламные действия
 *  - Списание бюджета кампании при каждом impression
 */
final readonly class AdEngineService
{
    public function __construct(
        private readonly Request $request,
        private TargetingCriteriaService $targetingCriteria,
        private ShortVideoAdService      $shortVideoAdService,
        private BigDataAggregatorService $bigData,
        private MarketingCampaignService $campaignService,
        private FraudControlService      $fraud,
        private AnonymizationService     $anonymizer,
        private AuditService             $audit,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Подобрать и показать рекламу пользователю.
     *
     * @param array{
     *   user_id: int,
     *   vertical: string,
     *   placement: string,
     *   correlation_id?: string,
     * } $request
     */
    public function serveAd(array $request): ?array
    {
        $userId        = $request['user_id'];
        $vertical      = $request['vertical'];
        $correlationId = $request['correlation_id'] ?? Str::uuid()->toString();

        $this->fraud->check($userId, 'ad_serve', 0, (string) $this->request->ip(), null, $correlationId);

        // 1. Получить таргетинговый профиль (без raw user_id)
        $targeting = $this->targetingCriteria->match($userId, $vertical);

        // 2. Найти лучший ad
        $ad = $this->findBestMatchingAd($targeting, $vertical);

        if ($ad === null) {
            throw new \DomainException('Operation returned no result');
        }

        // 3. Если шортс — генерируем через AI
        if (($ad['type'] ?? '') === 'shorts') {
            $shortUrl = $this->shortVideoAdService->getOrGenerate((int) $ad['id'], $userId, $correlationId);
            $ad['video_url'] = $shortUrl;
        }

        // 4. Трекинг impression (анонимизировано)
        $this->trackImpression($ad, $userId, $vertical, $correlationId);

        // 5. Списание бюджета кампании (1 impression)
        if (isset($ad['campaign_id'], $ad['cpm_kopecks'])) {
            $cpc = (int) round($ad['cpm_kopecks'] / 1000);
            $this->campaignService->recordSpend((int) $ad['campaign_id'], $cpc, $correlationId);
        }

        $this->logger->channel('audit')->info('Ad served', [
            'ad_id'          => $ad['id'] ?? null,
            'vertical'       => $vertical,
            'placement'      => $request['placement'],
            'correlation_id' => $correlationId,
        ]);

        return $ad;
    }

    /**
     * Трекинг клика по рекламе.
     */
    public function trackClick(int $adId, int $userId, string $correlationId): void
    {
        $anonId = $this->anonymizer->anonymizeUserId($userId);

        $this->bigData->insertMarketingEvent([
            'anonymized_user_id' => $anonId,
            'event_type'         => 'ad_click',
            'vertical'           => null,
            'device_type'        => $this->request->header('X-Device-Type', 'unknown'),
            'city_hash'          => 0,
            'created_at'         => now()->toIso8601String(),
            'correlation_id'     => $correlationId,
        ]);

        // Списание CPC бюджета кампании
        $ad = $this->db->table('ads')->find($adId);
        if ($ad !== null && isset($ad->campaign_id)) {
            $this->campaignService->recordSpend((int) $ad->campaign_id, (int) ($ad->cpc_kopecks ?? 0), $correlationId);
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function findBestMatchingAd(array $targeting, string $vertical): ?array
    {
        $ad = $this->db->table('ads')
            ->join('marketing_campaigns', 'marketing_campaigns.id', '=', 'ads.campaign_id')
            ->where('ads.vertical', $vertical)
            ->where('marketing_campaigns.status', 'active')
            ->whereRaw('marketing_campaigns.budget_kopecks > marketing_campaigns.spent_kopecks')
            ->when($targeting['is_b2b'], fn ($q) => $q->where('ads.audience', 'b2b'))
            ->when(! $targeting['is_b2b'], fn ($q) => $q->whereIn('ads.audience', ['b2c', 'all']))
            ->orderByDesc('ads.priority')
            ->first();

        return $ad !== null ? (array) $ad : null;
    }

    private function trackImpression(array $ad, int $userId, string $vertical, string $correlationId): void
    {
        $anonId = $this->anonymizer->anonymizeUserId($userId);

        $this->bigData->insertMarketingEvent([
            'anonymized_user_id' => $anonId,
            'event_type'         => 'ad_impression',
            'vertical'           => $vertical,
            'device_type'        => $this->request->header('X-Device-Type', 'unknown'),
            'city_hash'          => 0,
            'created_at'         => now()->toIso8601String(),
            'correlation_id'     => $correlationId,
        ]);
    }
}
