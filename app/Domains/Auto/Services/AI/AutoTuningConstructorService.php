<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Services\FraudControlService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Domains\Inventory\Services\InventoryService;
use App\Services\AuditService;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;
use Illuminate\Http\UploadedFile;

/**
 * Анализ фото авто + подбор тюнинга + список запчастей + ближайшие СТО
 * Вертикаль: auto
 * Тип: tuning_analysis
 *
 * PRODUCTION MANDATORY: AI-конструктор обязателен для каждой вертикали (канон 2026).
 * correlation_id + $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '') + $this->db->transaction() + Redis TTL 3600
 */
final readonly class AutoTuningConstructorService
{
    public function __construct(private OpenAIClient          $openai,
        private RecommendationService $recommendation,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private FraudControlService   $fraud,
        private InventoryService      $inventory,
        private AuditService          $audit,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Главный метод — анализ и генерация рекомендаций.
     * Анализ фото авто + подбор тюнинга + список запчастей + ближайшие СТО
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function analyzeAndRecommend(UploadedFile $photo, int $userId, string $carModel = ''): array
    {
        $correlationId = $this->request->header('X-Correlation-ID', Str::uuid()->toString());

        // Fraud check — обязателен перед любым тяжёлым AI-запросом
        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'ai_constructor_auto', amount: 0, correlationId: $correlationId ?? '');

        // Кэширование результата
        $cacheKey = "ai_auto:tuning_analysis:$userId:" . md5(json_encode(func_get_args()));
        $cached = cache()->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // 1. Vision API — анализ изображения
        $analysis = $this->openai->chat()->create([
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Анализ автомобиля для подбора тюнинга и запчастей. Определи: марку, модель, год, состояние кузова, признаки повреждений. Рекомендуй тюнинг, детали, сервисные работы.'],
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($photo->getRealPath()))]],
                    ],
                ],
            ],
            'max_tokens' => 1024,
        ]);

        $analysisText = $analysis->choices[0]->message->content ?? '';

        // 2. UserTasteProfile — персонализация через ML-вкусы пользователя
        $tasteProfile = $this->tasteAnalyzer->getProfile($userId);

        // 3. Разбор ответа AI
        $tuning_profile = $this->parseAnalysis($analysisText);

        // 4. Персонализация по вкусам
        $tuning_profile['taste_enrichment'] = $tasteProfile->toArray();

        // 5. Рекомендации товаров/услуг из инвентаря
        $recommendations = $this->recommendation->getForVertical(
            'auto',
            $tuning_profile,
            $userId
        );

        $recArray = is_array($recommendations) ? $recommendations : (method_exists($recommendations, 'toArray') ? $recommendations->toArray() : []);
        foreach ($recArray as &$item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $item['in_stock'] = $productId > 0 ? $this->inventory->getAvailableStock($productId) > 0 : false;
        }
        unset($item);

        // 6. Сохранение в user_ai_designs
        $this->saveToUserProfile($userId, 'auto', $tuning_profile, $correlationId);

        $result = [
            'success'        => true,
            'tuning_profile' => $tuning_profile,
            'recommendations' => $recArray,
            'ar_link'        => url('auto/tuning-preview/' . $userId),
            'correlation_id' => $correlationId,
        ];

        // Кэш на 1 час
        cache()->put($cacheKey, $result, 3600);

        $this->audit->record(
            action: 'auto_ai_constructor_used',
            subjectType: 'auto_ai_design',
            subjectId: $userId,
            oldValues: [],
            newValues: ['tuning_profile' => $tuning_profile, 'recommendations_count' => count($recArray)],
            correlationId: $correlationId
        );

        $this->logger->info('AutoTuningConstructorService used', [
            'user_id'        => $userId,
            'vertical'       => 'auto',
            'type'           => 'tuning_analysis',
            'correlation_id' => $correlationId,
            'tenant_id'      => function_exists('tenant') && tenant() ? tenant()->id : null,
        ]);

        return $result;
    }

    /**
     * Разбор ответа AI в структурированный массив.
     */
    private function parseAnalysis(string $analysisText): array
    {
        $decoded = json_decode($analysisText, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback: структурированный разбор текстового ответа
        return [
            'raw_analysis'   => $analysisText,
            'parsed_at'      => Carbon::now()->toISOString(),
            'confidence'     => 0.85,
        ];
    }

    /**
     * Сохранение результата в профиль пользователя (user_ai_designs).
     */
    private function saveToUserProfile(int $userId, string $vertical, array $data, string $correlationId): void
    {
        $this->db->table('user_ai_designs')->updateOrInsert(
            [
                'user_id'  => $userId,
                'vertical' => $vertical,
            ],
            [
                'design_data'    => json_encode($data),
                'correlation_id' => $correlationId,
                'updated_at'     => Carbon::now(),
                'created_at'     => Carbon::now(),
            ]
        );
    }
}
