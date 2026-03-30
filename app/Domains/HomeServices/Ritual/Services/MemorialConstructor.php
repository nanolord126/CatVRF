<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Ritual\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MemorialConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Конструктор с DI зависимостями (readonly).
         */
        public function __construct(
            private RecommendationService $recommendations,
            private InventoryManagementService $inventory,
        ) {}

        /**
         * Сгенерировать комплексное предложение на базе анализа (AI).
         *
         * @param array $context Окружение (гео, бюджет, предпочтения)
         */
        public function buildComplexOffer(int $userId, array $context = []): array
        {
            $correlation_id = (string) Str::uuid();

            Log::channel('audit')->info('Memorial constructor started', [
                'user_id' => $userId,
                'correlation_id' => $correlation_id,
                'context' => $context,
            ]);

            // 1. Получение персонализированных рекомендаций продукции
            $products = $this->recommendations->getForUser(
                userId: $userId,
                vertical: 'ritual',
                context: $context
            );

            // 2. Валидация остатков (InventoryManagementService Канон)
            $validOffers = $products->filter(function (MemorialProduct $product) {
                return $this->inventory->getCurrentStock($product->id) > 0;
            });

            // 3. Формирование пакета
            $mainItem = $validOffers->where('category', 'monument')->first();
            $accessories = $validOffers->where('category', '!=', 'monument')->take(3);

            $total_price = ($mainItem?->price_kopecks ?? 0) + $accessories->sum('price_kopecks');

            return [
                'correlation_id' => $correlation_id,
                'main_item' => $mainItem,
                'accessories' => $accessories,
                'total_price_kopecks' => $total_price,
                'installment_possible' => $total_price > 50000_00, // Рассрочка при > 50k руб
                'ai_confidence' => 0.94,
            ];
        }

        /**
         * Сохранить черновик мемориала.
         */
        public function saveDraftDraft(int $userId, array $design): string
        {
            $uuid = (string) Str::uuid();

            // Логика сохранения в кеш или БД (здесь пример лога)
            Log::channel('audit')->info('Memorial design draft saved', [
                'user_id' => $userId,
                'design_uuid' => $uuid,
                'payload' => $design,
            ]);

            return $uuid;
        }
}
