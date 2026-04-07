<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class OutfitConstructor
{

    public function __construct(private ImageAnalysisService $imageAnalysis,
            private RecommendationService $recommendation,
            private InventoryManagementService $inventory,
            private FraudControlService $fraud,
            private string $correlationId,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создать личную капсулу (Lookbook) на основе фото и параметров фигуры (Body-score)
         */
        public function construct(int $userId, \Illuminate\Http\UploadedFile $photo, array $params = []): AIConstructionResult
        {
            $this->logger->info('OutfitConstructor: starting fashion капсулы', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            return $this->db->transaction(function () use ($userId, $photo, $params) {
                // 1. Fraud Check (лимит генераций)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'fashion_ai_outfit', amount: 0, correlationId: $correlationId ?? '');

                // 2. Body-score: Анализ параметров фигуры (рост, тип, цветотип) через Vision AI
                $bodyAnalysis = $this->imageAnalysis->analyzeBody($photo);

                // 3. Генерация капсулы (Outfit) на неделю или под событие (wedding, office, casual)
                $occasion = $params['occasion'] ?? 'casual';
                $context = array_merge($params, [
                    'body_type' => $bodyAnalysis['body_type'] ?? 'standard',
                    'preferred_colors' => $bodyAnalysis['color_palette'] ?? ['neutral'],
                    'height_cm' => $bodyAnalysis['estimated_height'] ?? 175,
                    'occasion' => $occasion,
                    'vertical' => 'Fashion',
                ]);

                // Рекомендации по гардеробу из Inventory на базе профиля вкусов v2.0
                $recommendations = $this->recommendation->getForUser($userId, 'Fashion', $context);

                // 4. Проверка наличия и размеров (InventoryManagementService)
                $suggestions = $this->enrichSuggestions($recommendations, $bodyAnalysis);

                // 5. Формирование Lookbook
                $result = new AIConstructionResult(
                    vertical: 'Fashion',
                    type: 'design',
                    payload: [
                        'body_score' => $bodyAnalysis,
                        'capsule_name' => "Капсула '{$occasion}' для вашего типа фигуры",
                        'lookbook_url' => $this->generateLookbookUrl($bodyAnalysis, $suggestions),
                        'styling_tips' => [
                            'color_match' => "Выбранная палитра идеально подчеркивает ваш цветотип " . ($bodyAnalysis['season'] ?? 'лето') . ".",
                            'body_adjustment' => "Крой подчеркнёт линию плеч и визуально увеличит рост за счёт монохромных линий.",
                        ],
                    ],
                    suggestions: $suggestions,
                    confidence_score: (float)($bodyAnalysis['accuracy'] ?? 0.95),
                    correlation_id: $this->correlationId
                );

                // 6. Сохранение истории генерации
                $this->saveToDatabase($userId, $result);

                $this->logger->info('OutfitConstructor: finished creation', [
                    'user_id' => $userId,
                    'correlation_id' => $this->correlationId,
                    'items_count' => count($suggestions),
                ]);

                return $result;
            });
        }

        private function enrichSuggestions(\Illuminate\Support\Collection $recommendations, array $bodyAttributes): array
        {
            return $recommendations->map(function ($item) use ($bodyAttributes) {
                $inStock = $this->inventory->getCurrentStock($item->id) > 0;
                // Простая имитация проверки соответствия размера
                $sizeMatch = ($item->tags['size'] ?? 'M') === ($bodyAttributes['suggested_size'] ?? 'M');

                return array_merge($item->toArray(), [
                    'in_stock' => $inStock,
                    'size_match' => $sizeMatch,
                    'match_description' => $sizeMatch
                        ? 'Ваш размер в наличии.'
                        : 'Рекомендуется на размер больше для свободного кроя.',
                ]);
            })->toArray();
        }

        private function generateLookbookUrl(array $analysis, array $items): string
        {
            return "https://cdn.catvrf.com/ai/lookbooks/fashion-" . $this->correlationId . ".pdf";
        }

        private function saveToDatabase(int $userId, AIConstructionResult $result): void
        {
            $this->db->table('ai_constructions')->insert([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'tenant_id' => tenant()->id ?? 0,
                'vertical' => $result->vertical,
                'design_data' => json_encode($result->payload),
                'suggestions' => json_encode($result->suggestions),
                'correlation_id' => $result->correlation_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
}
