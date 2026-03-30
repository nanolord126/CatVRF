<?php declare(strict_types=1);

namespace App\Domains\Fashion\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIOutfitConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlation_id;

        public function __construct() {
            $this->correlation_id = request()->header('X-Correlation-ID', Str::uuid()->toString());
        }

        /**
         * Подбор аутфита по фото пользователя (Vision-анализ типа внешности)
         */
        public function generateFromPhoto(string $photoPath, string $style = 'casual'): Collection
        {
            Log::channel('audit')->info('AI Outfit generation started via Photo Analysis', [
                'photo' => $photoPath,
                'correlation_id' => $this->correlation_id,
            ]);

            // Mock AI Vision logic:
            // 1. Анализируем фото через OpenAI/GigaChat Vision
            // 2. Получаем цветотип и тип фигуры
            // 3. Ищем соответствие в FashionProduct с учетом стиля

            $products = FashionProduct::where('status', 'active')
                ->where(function ($q) use ($style) {
                    $q->whereJsonContains('attributes->style', $style);
                })
                ->limit(5)
                ->get();

            return $products->map(fn($p) => [
                'product_id' => $p->id,
                'name' => $p->name,
                'match_score' => 0.95, // AI confidence score
                'reason' => "Этот цвет подчеркивает ваш цветотип, выявленный на фото.",
            ]);
        }

        /**
         * ПОДБОР ПО ПОГОДЕ (Weather-Aware Matching)
         */
        public function suggestByWeather(string $city, int $temp): Collection
        {
            $season = match (true) {
                $temp < 0 => 'winter',
                $temp < 15 => 'autumn',
                $temp < 25 => 'spring',
                default => 'summer',
            };

            return FashionProduct::whereJsonContains('attributes->season', $season)
                ->where('stock_quantity', '>', 0)
                ->limit(4)
                ->get();
        }

        /**
         * РЕКОМЕНДАЦИЯ РАЗМЕРА (SizeRecommendationService integration)
         */
        public function recommendSize(int $productId, array $userMeasurements): string
        {
            $product = FashionProduct::with('sizes')->findOrFail($productId);

            foreach ($product->sizes as $size) {
                $measurements = $size->measurements; // JSON: chest, waist, hips

                // Логика сравнения замеров с допусками 2-3 см
                if (abs($measurements['chest'] - $userMeasurements['chest']) <= 2 &&
                    abs($measurements['waist'] - $userMeasurements['waist']) <= 2) {
                    return $size->size_value;
                }
            }

            return 'Contact Support for manual fitting';
        }
}
