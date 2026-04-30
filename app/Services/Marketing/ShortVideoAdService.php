<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use Psr\Log\LoggerInterface;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Services\AuditService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;

/**
 * ShortVideoAdService — генерация коротких рекламных видео через AI.
 *
 * Правила канона:
 *  - Длина шортса: 15–30 секунд
 *  - Генерация через GPT-4o Video / Runway / Pika (настраивается через config)
 *  - A/B-тестирование версий
 *  - Трекинг: views, watch_time, clicks, shares
 *  - Кэширование сгенерированных видео в Redis (TTL 86400 сек)
 *  - Результат строится на UserTasteProfile + вертикальном AI-конструкторе
 */
final readonly class ShortVideoAdService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
        private readonly UserTasteAnalyzerService $tasteAnalyzer,
        private readonly AuditService $audit,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly HttpFactory $http,) {}

    /**
     * Получить или сгенерировать шортс для рекламы.
     * Кэширование: если для данного ad+user уже есть — возвращаем.
     */
    public function getOrGenerate(int $adId, int $userId, string $correlationId): string
    {
        $cacheKey = "short_video_ad:{$adId}:u{$userId}";

        return cache()->remember($cacheKey, 86400, function () use ($adId, $userId, $correlationId): string {
            return $this->generate($adId, $userId, $correlationId);
        });
    }

    /**
     * Генерировать шортс для рекламы на основе профиля пользователя.
     */
    public function generate(int $adId, int $userId, string $correlationId): string
    {
        $ad      = $this->db->table('ads')->find($adId);
        if ($ad === null) {
            throw new \RuntimeException("Ad #{$adId} not found");
        }

        // Получаем taste profile из таблицы (там уже аггрегированные данные)
        $tasteRow = $this->db->table('user_taste_profiles')->where('user_id', $userId)->first();
        $taste    = $tasteRow !== null
            ? (json_decode($tasteRow->profile_data ?? '{}', true) ?? [])
            : [];

        $prompt = $this->buildPromptFromTaste($taste, (array) $ad);

        // Генерация видео через AI (GPT-4o Video / Runway / Pika)
        $videoUrl = $this->callAiVideoGenerator($prompt, (array) $ad);

        // Сохраняем результат
        $shortId = $this->db->table('short_video_ads')->insertGetId([
            'ad_id'          => $adId,
            'user_id'        => $userId,   // для связи с аналитикой
            'url'            => $videoUrl,
            'duration_sec'   => 15,        // 15-30 секунд
            'ab_variant'     => 'A',
            'correlation_id' => $correlationId,
            'created_at'     => CarbonImmutable::now(),
            'updated_at'     => CarbonImmutable::now(),
        ]);

        $this->audit->record('short_video_generated', 'short_video_ads', $shortId, [], ['ad_id' => $adId], $correlationId);

        $this->logger->channel('audit')->info('Short video ad generated', [
            'short_id'       => $shortId,
            'ad_id'          => $adId,
            'video_url'      => $videoUrl,
            'correlation_id' => $correlationId,
        ]);

        return $videoUrl;
    }

    /**
     * Трекинг просмотра шортса.
     */
    public function trackView(int $shortId, int $userId, int $watchTimeSec, string $correlationId): void
    {
        $this->db->table('short_video_ad_views')->insert([
            'short_id'       => $shortId,
            'user_id'        => $userId,
            'watch_time_sec' => $watchTimeSec,
            'correlation_id' => $correlationId,
            'viewed_at'      => CarbonImmutable::now(),
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function buildPromptFromTaste(mixed $taste, array $ad): string
    {
        $tasteArray = is_array($taste) ? $taste : (method_exists($taste, 'toArray') ? $taste->toArray() : []);

        $vertical   = $ad['vertical'] ?? 'general';
        $categories = implode(', ', array_keys($tasteArray['categories'] ?? []));

        return "Создай рекламный шортс 15-30 секунд для вертикали '{$vertical}'. "
            ."Предпочтения пользователя: {$categories}. "
            ."Продукт: {$ad['title']}. "
            .'Стиль: современный, динамичный. Язык: русский.';
    }

    private function callAiVideoGenerator(string $prompt, array $ad): string
    {
        $driver = $this->config->get('services.ai_video.driver');
        if (! $driver) {
            throw new \RuntimeException('AI video generator driver not configured');
        }

        try {
            // В production — вызов реального AI-видео API (Runway, Pika, GPT-4o Video)
            $response = $this->http->withToken($this->config->get('services.ai_video.api_key'))
                ->timeout(30)
                ->post($this->config->get('services.ai_video.endpoint'), [
                    'prompt'    => $prompt,
                    'duration'  => 15,
                    'thumbnail' => $ad['image_url'] ?? null,
                ]);

            if (! $response->successful()) {
                $this->logger->channel('audit')->error('AI video API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException("AI video API failed: {$response->status()}");
            }

            $videoUrl = $response->json('video_url', '');

            if (empty($videoUrl)) {
                throw new \RuntimeException('AI video API returned empty video_url');
            }

            return $videoUrl;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->logger->channel('audit')->error('AI video API connection error', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('AI video service unavailable', 0, $e);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->logger->channel('audit')->error('AI video API request error', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('AI video request failed', 0, $e);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('AI video generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('AI video generation failed', 0, $e);
        }
    }
}
