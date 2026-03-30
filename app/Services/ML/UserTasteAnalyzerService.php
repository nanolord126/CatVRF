<?php declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserTasteAnalyzerService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private string $correlationId
        ) {}

        /**
         * Запустить полный анализ профиля вкусов пользователя
         */
        public function analyzeAndSaveUserProfile(User $user): void
        {
            Log::channel('audit')->info("User taste profile analysis started", [
                'user_id' => $user->id,
                'correlation_id' => $this->correlationId,
            ]);

            $profile = DB::transaction(function () use ($user) {
                // 1. Анализ категорий (просмотры)
                $viewedCategories = $this->analyzeCategories($user->id);

                // 2. Ценовой диапазон (покупки)
                $priceRange = $this->analyzePriceRange($user->id);

                // 3. Предпочтения по размерам (Fashion/Beauty)
                $preferredSizes = $this->analyzeSizes($user->id);

                // 4. Предпочтения по цветам
                $preferredColors = $this->analyzeColors($user->id);

                // 5. Бренды
                $preferredBrands = $this->analyzeBrands($user->id);

                $data = [
                    'categories' => $viewedCategories,
                    'price_range' => $priceRange,
                    'preferred_sizes' => $preferredSizes,
                    'preferred_colors' => $preferredColors,
                    'preferred_brands' => $preferredBrands,
                    'analyzed_at' => now()->toIso8601String(),
                    'version' => '2.0',
                ];

                // Обновляем модель пользователя или связанный профиль
                $user->update([
                    'taste_profile' => $data,
                ]);

                return $data;
            });

            Log::channel('audit')->info("User taste profile analyzed successfully", [
                'user_id' => $user->id,
                'correlation_id' => $this->correlationId,
                'price_range' => $profile['price_range'],
            ]);
        }

        private function analyzeCategories(int $userId): array
        {
            return DB::table('product_views')
                ->where('user_id', $userId)
                ->groupBy('product_category')
                ->selectRaw('product_category, COUNT(*) as count')
                ->orderByRaw('count DESC')
                ->limit(10)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->product_category => $row->count / 10])
                ->toArray();
        }

        private function analyzePriceRange(int $userId): string
        {
            $avgPrice = DB::table('orders')
                ->where('user_id', $userId)
                ->avg('total_price') ?? 0;

            return match (true) {
                $avgPrice < 1000 => 'budget',
                $avgPrice < 5000 => 'mid',
                $avgPrice < 15000 => 'premium',
                default => 'luxury',
            };
        }

        private function analyzeSizes(int $userId): array
        {
            return DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.user_id', $userId)
                ->groupBy('products.size')
                ->selectRaw('products.size, COUNT(*) as count')
                ->pluck('count', 'size')
                ->toArray();
        }

        private function analyzeColors(int $userId): array
        {
            return DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.user_id', $userId)
                ->groupBy('products.color')
                ->selectRaw('products.color, COUNT(*) as count')
                ->pluck('count', 'color')
                ->toArray();
        }

        private function analyzeBrands(int $userId): array
        {
            return DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.user_id', $userId)
                ->groupBy('products.brand')
                ->selectRaw('products.brand, COUNT(*) as count')
                ->orderByRaw('count DESC')
                ->limit(5)
                ->pluck('count', 'brand')
                ->toArray();
        }
}
