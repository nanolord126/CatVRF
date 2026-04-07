<?php declare(strict_types=1);

namespace App\Services\ML;




use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class UserTasteAnalyzerService
{

    public function __construct(
        private readonly Request $request,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    private function correlationId(): string
    {
        return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
    }

        /**
         * Запустить полный анализ профиля вкусов пользователя
         */
        public function analyzeAndSaveUserProfile(User $user): void
        {
            $this->logger->channel('audit')->info("User taste profile analysis started", [
                'user_id' => $user->id,
                'correlation_id' => $this->correlationId(),
            ]);

            $profile = $this->db->transaction(function () use ($user) {
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

            $this->logger->channel('audit')->info("User taste profile analyzed successfully", [
                'user_id' => $user->id,
                'correlation_id' => $this->correlationId(),
                'price_range' => $profile['price_range'],
            ]);
        }

        private function analyzeCategories(int $userId): array
        {
            return $this->db->table('product_views')
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
            $avgPrice = $this->db->table('orders')
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
            return $this->db->table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.user_id', $userId)
                ->groupBy('products.size')
                ->selectRaw('products.size, COUNT(*) as count')
                ->pluck('count', 'size')
                ->toArray();
        }

        private function analyzeColors(int $userId): array
        {
            return $this->db->table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.user_id', $userId)
                ->groupBy('products.color')
                ->selectRaw('products.color, COUNT(*) as count')
                ->pluck('count', 'color')
                ->toArray();
        }

        private function analyzeBrands(int $userId): array
        {
            return $this->db->table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.user_id', $userId)
                ->groupBy('products.brand')
                ->selectRaw('products.brand, COUNT(*) as count')
                ->orderByRaw('count DESC')
                ->limit(5)
                ->pluck('count', 'brand')
                ->toArray();
        }

    /**
     * Получить сохранённый профиль вкусов пользователя.
     * Если профиль ещё не был проанализирован — возвращает null.
     * AI-конструкторы должны обрабатывать null gracefully.
     */
    public function getProfile(int $userId): ?object
    {
        $raw = $this->db->table('users')
            ->where('id', $userId)
            ->value('taste_profile');

        if ($raw === null) {
            throw new \DomainException('Operation returned no result');
        }

        $data = is_string($raw) ? json_decode($raw, false) : (object) $raw;

        // Нормализуем поля для каждой вертикали
        return (object) [
            'categories'         => (array) ($data->categories         ?? []),
            'price_range'        => $data->price_range                 ?? 'mid',
            'preferred_sizes'    => (array) ($data->preferred_sizes    ?? []),
            'preferred_colors'   => (array) ($data->preferred_colors   ?? []),
            'preferred_brands'   => (array) ($data->preferred_brands   ?? []),
            // вертикальные предпочтения (могут быть null у новых клиентов)
            'fashion_preferences'  => (array) ($data->fashion_preferences  ?? []),
            'food_preferences'     => (array) ($data->food_preferences     ?? []),
            'interior_preferences' => (array) ($data->interior_preferences ?? []),
            'fitness_preferences'  => (array) ($data->fitness_preferences  ?? []),
            'hotel_preferences'    => (array) ($data->hotel_preferences    ?? []),
            'travel_preferences'   => (array) ($data->travel_preferences   ?? []),
            'analyzed_at'          => $data->analyzed_at                   ?? null,
        ];
    }
}
