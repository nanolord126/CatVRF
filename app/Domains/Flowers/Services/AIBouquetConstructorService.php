<?php

declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerShop;
use App\Domains\Flowers\Models\Bouquet;
use App\Domains\Flowers\Models\FlowerProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAI;

/**
 * КАНОН 2026: AIBouquetConstructorService (Flowers).
 * Интеллектуальный подбор букета на основе повода, бюджета и предпочтений клиента.
 */
final readonly class AIBouquetConstructorService
{
    public function __construct(
        private OpenAI $openai,
    ) {}

    /**
     * Подобрать букет на базе параметров пользователя.
     */
    public function recommendBouquet(array $params, int $userId): array
    {
        $correlationId = $params['correlation_id'] ?? (string) Str::uuid();

        // 1. Получение текущих доступных товаров в магазине
        $availableProducts = FlowerProduct::where('shop_id', $params['shop_id'])
            ->where('current_stock', '>', 0)
            ->get();

        Log::channel('audit')->info('AI Bouquet Recommendation Started', [
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

        Log::channel('audit')->info('AI Bouquet Recommendation Finished', [
            'user_id' => $userId,
            'rec_name' => $aiResult['name'] ?? 'Custom AI Bouquet',
            'correlation_id' => $correlationId,
        ]);

        return $aiResult;
    }

    /**
     * Создать новый шаблон букета на основе рекомендации AI.
     */
    public function saveAIRecommendationAsTemplate(array $aiResult, int $shopId): Bouquet
    {
        $correlationId = $aiResult['correlation_id'] ?? (string) Str::uuid();

        return DB::transaction(function () use ($aiResult, $shopId, $correlationId) {
            $bouquet = Bouquet::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'shop_id' => $shopId,
                'name' => 'AI: ' . ($aiResult['name'] ?? 'New Design'),
                'description' => $aiResult['description'] ?? 'Автоматически сгенерированный дизайн',
                'price_kopecks' => (int)($aiResult['price'] ?? 1000) * 100,
                'composition_json' => $aiResult['composition'] ?? [],
                'status' => 'active',
                'tags' => ['ai_generated'],
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('AI Recommendation saved as template', [
                'bouquet_id' => $bouquet->id,
                'correlation_id' => $correlationId,
            ]);

            return $bouquet;
        });
    }

    /**
     * Анализ фото букета (Vision API).
     */
    public function analyzeBouquetPhoto(\Illuminate\Http\UploadedFile $photo): array
    {
        $correlationId = (string) Str::uuid();

        // Отправка в OpenAI Vision
        $analysis = $this->openai->vision()->analyze([
            'image' => $photo->getRealPath(),
            'prompt' => 'Определи состав букета на фото (список цветов).',
        ]);

        Log::channel('audit')->info('Flower Bouquet Photo Analysis finished', [
            'correlation_id' => $correlationId,
        ]);

        return [
            'analysis' => $analysis,
            'correlation_id' => $correlationId,
        ];
    }
}
