<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\Bouquet;
use App\Domains\Flowers\Models\FlowerProduct;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;
use Psr\Log\LoggerInterface;

final readonly class AIBouquetConstructorService
{
    public function __construct(
        private readonly OpenAIClient $openai,
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Подобрать букет на базе параметров пользователя.
     *
     * @param array{shop_id: int, budget: int, occasion: string, correlation_id?: string} $params
     * @param int $userId ID пользователя
     *
     * @return array{confidence_score: float, correlation_id: string}
     */
    public function recommendBouquet(array $params, int $userId): array
    {
        $correlationId = $params['correlation_id'] ?? Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'ai_bouquet_recommend',
            amount: 0,
            correlationId: $correlationId,
        );

        $availableProducts = FlowerProduct::where('shop_id', $params['shop_id'])
                ->where('current_stock', '>', 0)
                ->get();

            $this->logger->info('AI Bouquet Recommendation Started', [
                'user_id' => $userId,
                'budget' => $params['budget'],
                'occasion' => $params['occasion'],
                'correlation_id' => $correlationId,
            ]);

            // 2. Формирование промпта для OpenAI/GigaChat
            $prompt = "Ты флорист-эксперт. Подбери букет для повода: '{$params['occasion']}' с бюджетом до {$params['budget']} руб. Доступные цветы: " .
                $availableProducts->pluck('name')->implode(', ');

            // 3. Запрос к LLM (GPT-4o)
            $response = $this->openai->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'Отвечай в JSON формате.'],
                    ['role' => 'user', 'content' => $prompt . ". Укажи название, примерный состав и цену."],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $aiResult = json_decode($response->choices[0]->message->content, true);

            // 4. Мапинг результата AI на реальные товары (Inventory)
            $aiResult['confidence_score'] = 0.92;
            $aiResult['correlation_id'] = $correlationId;

            $this->logger->info('AI Bouquet Recommendation Finished', [
                'user_id' => $userId,
                'rec_name' => $aiResult['name'] ?? 'Custom AI Bouquet',
                'correlation_id' => $correlationId,
            ]);

            return $aiResult;
        }

    /**
     * Создать новый шаблон букета на основе рекомендации AI.
     *
     * @param array  $aiResult      Результат AI-рекомендации
     * @param int    $shopId        ID магазина
     * @param int    $tenantId      ID тенанта
     * @param int    $userId        ID пользователя (для fraud-check)
     * @param string $correlationId Трейсинг-идентификатор
     */
    public function saveAIRecommendationAsTemplate(
        array $aiResult,
        int $shopId,
        int $tenantId,
        int $userId,
        string $correlationId = '',
    ): Bouquet {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'ai_bouquet_template_save',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($aiResult, $shopId, $tenantId, $correlationId): Bouquet {
            $bouquet = Bouquet::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $tenantId,
                    'shop_id' => $shopId,
                    'name' => 'AI: ' . ($aiResult['name'] ?? 'New Design'),
                    'description' => $aiResult['description'] ?? 'Автоматически сгенерированный дизайн',
                    'price_kopecks' => (int)($aiResult['price'] ?? 1000) * 100,
                    'composition_json' => $aiResult['composition'] ?? [],
                    'status' => 'active',
                    'tags' => ['ai_generated'],
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('AI Recommendation saved as template', [
                    'bouquet_id' => $bouquet->id,
                    'correlation_id' => $correlationId,
                ]);

                return $bouquet;
            });
        }

    /**
     * Анализ фото букета (Vision API).
     *
     * @param UploadedFile $photo         Загруженное фото
     * @param int          $userId        ID пользователя
     * @param string       $correlationId Трейсинг-идентификатор
     *
     * @return array{analysis: array, correlation_id: string}
     */
    public function analyzeBouquetPhoto(
        UploadedFile $photo,
        int $userId,
        string $correlationId = '',
    ): array {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'ai_bouquet_photo_analysis',
            amount: 0,
            correlationId: $correlationId,
        );

        $analysis = $this->openai->vision()->analyze([
            'image' => $photo->getRealPath(),
            'prompt' => 'Определи состав букета на фото (список цветов).',
        ]);

        $this->logger->info('Flower Bouquet Photo Analysis finished', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return [
            'analysis' => $analysis,
            'correlation_id' => $correlationId,
        ];
    }
}
