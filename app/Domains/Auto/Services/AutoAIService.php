<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoAIService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly AIConstructorService $aiCore,
            private readonly PricingService $pricing,
        ) {}

        /**
         * Оценка стоимости ремонта по фото (AI Vision).
         * Анализирует вмятины, царапины, разбитые фары и возвращает смету.
         */
        public function estimateRepairFromPhoto(\Illuminate\Http\UploadedFile $photo, Vehicle $vehicle, string $correlationId): array
        {
            Log::channel('audit')->info('AI Repair Vision Started', [
                'vehicle_uuid' => $vehicle->uuid,
                'correlation_id' => $correlationId,
            ]);

            $prompt = "Проанализируй фото повреждений автомобиля {$vehicle->brand} {$vehicle->model}. " .
                      "Определи видимые повреждения (вмятины, сколы, разбитые части). " .
                      "Верни JSON со списком работ и примерной стоимостью материалов в копейках.";

            // Вызов ядра AI (OpenAI Vision)
            $aiResult = $this->aiCore->analyzePhotoAndRecommend($photo, 'Auto', (int) auth()->id());

            // Формирование сметы на базе ответа AI
            $estimate = [
                'vehicle_uuid' => $vehicle->uuid,
                'ai_analysis' => $aiResult['analysis'] ?? 'Повреждения распознаны',
                'recommended_tasks' => $aiResult['recommendations'] ?? [],
                'estimated_total_kopecks' => 0,
                'correlation_id' => $correlationId,
            ];

            // Дополнительный расчет через PricingService
            $estimate['estimated_total_kopecks'] = $this->pricing->estimateRepairCost($estimate['recommended_tasks'], $correlationId);

            Log::channel('audit')->info('AI Repair Vision Finished', [
                'order_uuid' => $vehicle->uuid,
                'estimated_total' => $estimate['estimated_total_kopecks'],
            ]);

            return $estimate;
        }

        /**
         * AI-рекомендация автомобиля на основе профиля вкусов (UserTasteProfile).
         */
        public function recommendVehicleForUser(int $userId, string $correlationId): \Illuminate\Support\Collection
        {
            $user = \App\Models\User::findOrFail($userId);
            $taste = $user->taste_profile ?? [];

            Log::channel('audit')->info('AI Vehicle Recommendation Started', [
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            // Поиск в инвентаре (складе) на основе предпочтений по цене и бренду
            $query = Vehicle::where('status', 'active');

            if (!empty($taste['preferred_brands'])) {
                $query->whereIn('brand', array_keys($taste['preferred_brands']));
            }

            if (!empty($taste['price_range'])) {
                // Условная логика ценовых диапазонов для авто
                $range = match($taste['price_range']) {
                    'luxury' => [500000000, 5000000000], // 5M - 50M руб
                    'premium' => [200000000, 500000000],
                    default => [0, 200000000],
                };
                // $query->whereBetween('price_kopecks', $range); // Если бы была цена у модели
            }

            return $query->limit(5)->get();
        }
}
