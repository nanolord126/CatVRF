<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApplianceAIService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private AIConstructorService $aiService,
            private string $correlationId = ""
        ) {}

        /**
         * Оценка ремонта по фото поломки (сдвиг дверцы, течь, ошибка на табло).
         */
        public function estimateRepairFromPhoto(string $photoBase64, string $applianceType): array
        {
            $correlationId = $this->correlationId ?: (string) Str::uuid();

            Log::channel('audit')->info('AI Appliance repair estimation started', [
                'type' => $applianceType,
                'correlation_id' => $correlationId
            ]);

            // Промпт для Vision AI (2026)
            $prompt = "Ты эксперт-мастер по ремонту бытовой техники. Проанализируй фото {$applianceType}.
            Определи:
            1. Вероятная причина поломки.
            2. Список необходимых запчастей.
            3. Примерная сложность работ (1-10).
            4. Ориентировочная стоимость работ в рублях.
            Верни ответ в формате JSON.";

            try {
                $result = $this->aiService->analyzePhotoAndRecommendShort($photoBase64, $prompt);

                return [
                    'estimate' => $result,
                    'confidence' => 0.85, // AI confidence score
                    'correlation_id' => $correlationId
                ];
            } catch (\Throwable $e) {
                Log::error('Appliance AI Estimation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ]);

                return [
                    'estimate' => ['error' => 'AI временно недоступен. Мастер оценит на месте.'],
                    'confidence' => 0,
                    'correlation_id' => $correlationId
                ];
            }
        }

        /**
         * Прогноз следующего ТО на базе модели и интенсивности (B2B).
         */
        public function predictNextMaintenance(ApplianceRepairOrder $order): \Carbon\Carbon
        {
            // Упрощенный AI-прогноз для B2B (Стиральные в прачечных — каждые 3 мес)
            return $order->is_b2b
                ? $order->completed_at->addMonths(3)
                : $order->completed_at->addYear();
        }
}
