<?php

declare(strict_types=1);

namespace App\Services\KYB;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use App\Models\AdverseMediaAlert;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;

final readonly class AdverseMediaScreeningService
{
    use WithAuditLogging;

    private const ALERT_THRESHOLD = 0.7; // Sentiment confidence threshold

    public function __construct(
        private readonly BusDispatcher $bus,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly Repository $config,
    ) {}

    /**
     * Screen entity for adverse media
     */
    public function screenEntity(
        int $kybVerificationId,
        array $entity,
        string $correlationId = ''
    ): array {
        $this->fraudControl->check(
            userId: null,
            operationType: 'adverse_media_screening',
            amount: 0,
            correlationId: $correlationId,
        );

        // Search Google News
        $googleResults = $this->searchGoogleNews($entity['entity_name']);

        // Search Yandex News (for Russian sources)
        $yandexResults = $this->searchYandexNews($entity['entity_name']);

        // Combine and deduplicate
        $allArticles = array_merge($googleResults, $yandexResults);
        $uniqueArticles = $this->deduplicateArticles($allArticles);

        // Analyze sentiment for each article
        $alerts = [];
        foreach ($uniqueArticles as $article) {
            $sentiment = $this->analyzeSentiment($article['title'].' '.($article['description'] ?? ''));

            if ($sentiment['sentiment'] === 'negative' && $sentiment['confidence'] > self::ALERT_THRESHOLD) {
                $alert = $this->createAdverseMediaAlert(
                    $kybVerificationId,
                    $entity,
                    $article,
                    $sentiment,
                    $correlationId
                );
                $alerts[] = $alert;
            }
        }

        // Auto-notify for critical alerts
        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'critical') {
                $this->notifyCriticalAlert($alert);
            }
        }

        return [
            'entity_name' => $entity['entity_name'],
            'articles_scanned' => count($uniqueArticles),
            'alerts_generated' => count($alerts),
            'alerts' => $alerts,
        ];
    }

    /**
     * Screen all entities in UBO chain
     */
    public function screenAllEntities(
        int $kybVerificationId,
        array $uboChain,
        string $correlationId = ''
    ): array {
        $results = [];

        foreach ($uboChain as $entity) {
            $results[] = $this->screenEntity($kybVerificationId, $entity, $correlationId);
        }

        return $results;
    }

    /**
     * Continuous monitoring for existing entities
     */
    public function monitorEntity(
        int $kybVerificationId,
        string $entityName,
        string $correlationId = ''
    ): void {
        $entity = ['entity_name' => $entityName];
        $this->screenEntity($kybVerificationId, $entity, $correlationId);
    }

    private function searchGoogleNews(string $entityName): array
    {
        $apiKey = $this->config->get('kyb.adverse_media.google_news.api_key');
        $apiUrl = $this->config->get('kyb.adverse_media.google_news.api_url');

        if (! $apiKey || ! $apiUrl) {
            $this->log->warning('Google News API not configured');

            return [];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->get($apiUrl, [
                    'q' => $entityName,
                    'language' => 'ru,en',
                    'sortBy' => 'publishedAt',
                    'pageSize' => 50,
                ]);

            if (! $response->successful()) {
                $this->log->warning('Google News search failed', [
                    'entity_name' => $entityName,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            return $data['articles'] ?? [];
        } catch (\Throwable $e) {
            $this->log->error('Google News search error', [
                'entity_name' => $entityName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function searchYandexNews(string $entityName): array
    {
        $apiKey = $this->config->get('kyb.adverse_media.yandex_news.api_key');
        $apiUrl = $this->config->get('kyb.adverse_media.yandex_news.api_url');

        if (! $apiKey || ! $apiUrl) {
            $this->log->warning('Yandex News API not configured');

            return [];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->get($apiUrl, [
                    'query' => $entityName,
                    'lang' => 'ru',
                    'limit' => 50,
                ]);

            if (! $response->successful()) {
                $this->log->warning('Yandex News search failed', [
                    'entity_name' => $entityName,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            return $data['articles'] ?? [];
        } catch (\Throwable $e) {
            $this->log->error('Yandex News search error', [
                'entity_name' => $entityName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function deduplicateArticles(array $articles): array
    {
        $seen = [];
        $unique = [];

        foreach ($articles as $article) {
            $key = md5($article['title'].($article['url'] ?? ''));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $article;
            }
        }

        return $unique;
    }

    private function analyzeSentiment(string $text): array
    {
        // For now, use rule-based sentiment analysis
        // In production, integrate with OpenAI or similar for AI sentiment
        $negativeKeywords = [
            'fraud', 'scandal', 'corruption', 'мошенничество',
            'коррупция', 'скандал', 'обман', 'мошенник',
            'манипуляция', 'отмывание', 'незаконный',
        ];

        $textLower = strtolower($text);
        $matchCount = 0;

        foreach ($negativeKeywords as $keyword) {
            if (str_contains($textLower, $keyword)) {
                $matchCount++;
            }
        }

        if ($matchCount >= 2) {
            return [
                'sentiment' => 'negative',
                'confidence' => min(0.7 + ($matchCount * 0.1), 0.95),
                'key_topics' => $this->extractKeyTopics($text),
            ];
        }

        if ($matchCount === 1) {
            return [
                'sentiment' => 'negative',
                'confidence' => 0.6,
                'key_topics' => $this->extractKeyTopics($text),
            ];
        }

        return [
            'sentiment' => 'neutral',
            'confidence' => 0.5,
            'key_topics' => [],
        ];
    }

    private function extractKeyTopics(string $text): array
    {
        $topics = [];

        if (str_contains(strtolower($text), 'финанс') || str_contains(strtolower($text), 'финансов')) {
            $topics[] = 'financial';
        }
        if (str_contains(strtolower($text), 'юридическ') || str_contains(strtolower($text), 'суд')) {
            $topics[] = 'legal';
        }
        if (str_contains(strtolower($text), 'налог')) {
            $topics[] = 'tax';
        }

        return $topics;
    }

    private function createAdverseMediaAlert(
        int $kybVerificationId,
        array $entity,
        array $article,
        array $sentiment,
        string $correlationId
    ): array {
        $alert = AdverseMediaAlert::create([
            'kyb_verification_id' => $kybVerificationId,
            'ubo_chain_id' => isset($entity['id']) && $entity['id'] !== null ? (int) $entity['id'] : null,
            'entity_name' => $entity['entity_name'],
            'entity_inn' => $entity['entity_inn'] ?? null,
            'alert_type' => $this->determineAlertType($article),
            'severity' => $this->determineSeverity($sentiment),
            'source_name' => $article['source']['name'] ?? 'Unknown',
            'source_url' => $article['url'] ?? null,
            'publication_date' => isset($article['publishedAt']) ? Carbon::parse($article['publishedAt']) : CarbonImmutable::now(),
            'title' => $article['title'] ?? '',
            'summary' => $article['description'] ?? '',
            'full_content' => $article['content'] ?? null,
            'relevance_score' => (int) round($sentiment['confidence'] * 100),
            'is_verified' => false,
            'key_topics' => $sentiment['key_topics'],
            'language' => 'ru',
            'source_credibility_score' => 50,
            'enable_monitoring' => true,
            'last_monitored_at' => CarbonImmutable::now(),
            'next_monitor_at' => CarbonImmutable::now()->addHours(6),
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->record(
            action: 'adverse_media_alert_created',
            subjectType: AdverseMediaAlert::class,
            subjectId: $alert->id,
            newValues: [
                'kyb_verification_id' => $kybVerificationId,
                'entity_name' => $entity['entity_name'],
                'severity' => $alert->severity,
                'source' => $alert->source_name,
            ],
            correlationId: $correlationId,
        );

        return $alert->toArray();
    }

    private function determineAlertType(array $article): string
    {
        $title = strtolower($article['title'] ?? '');
        $description = strtolower($article['description'] ?? '');
        $text = $title.' '.$description;

        if (str_contains($text, 'мошенничество') || str_contains($text, 'fraud')) {
            return 'fraud';
        }
        if (str_contains($text, 'коррупция') || str_contains($text, 'corruption')) {
            return 'corruption';
        }
        if (str_contains($text, 'отмывание') || str_contains($text, 'laundering')) {
            return 'money_laundering';
        }
        if (str_contains($text, 'санкци')) {
            return 'sanctions';
        }

        return 'negative_coverage';
    }

    private function determineSeverity(array $sentiment): string
    {
        $confidence = $sentiment['confidence'];

        if ($confidence >= 0.9) {
            return 'critical';
        }
        if ($confidence >= 0.8) {
            return 'high';
        }
        if ($confidence >= 0.7) {
            return 'medium';
        }

        return 'low';
    }

    private function notifyCriticalAlert(array $alert): void
    {
        $this->log->critical('Critical adverse media alert', [
            'alert_id' => $alert['id'],
            'entity_name' => $alert['entity_name'],
            'title' => $alert['title'],
            'source' => $alert['source_name'],
        ]);

        // In production, send to Slack, Telegram, email, etc.
        // Example: $this->bus->dispatch(new CriticalAdverseMediaNotification($alert));
    }
}
