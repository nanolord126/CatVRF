# AI CONSTRUCTORS & ML ANALYSIS 2026

**Версия:** 1.0  
**Статус:** PRODUCTION MANDATORY  
**Дата:** 25.03.2026

---

## 🤖 AI КОНСТРУКТОРЫ ДЛЯ КАЖДОЙ ВЕРТИКАЛИ

**Каждая вертикаль ДОЛЖНА иметь AI-конструктор** для создания/дизайна своих товаров/услуг.

---

### 1. BEAUTY — AI Конструктор Образа

**Функциональность:**
```
1. Загрузить фото лица пользователя
2. AI анализирует: тип лица, тон кожи, цвет волос, форму бровей
3. Рекомендует: причёски, окраски, макияжи, услуги мастеров
4. Виртуальная примерка (AR): примерить причёску или макияж на фото
5. Сохранить понравившиеся варианты в профиль ("Мой стиль")
6. Получить список услуг и мастеров с ценами
```

**Реализация**
```php
declare(strict_types=1);

namespace App\Domains\Beauty\Services\AI;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;

final readonly class BeautyImageConstructorService
{
    public function __construct(
        private readonly Client $openai,
        private readonly \App\Services\RecommendationService $recommendation,
    ) {}

    public function analyzePhotoAndRecommend(
        \Illuminate\Http\UploadedFile $photo,
        int $userId,
    ): array {
        try {
            // 1. Отправить фото в OpenAI Vision API
            $analysis = $this->openai->vision()->analyze([
                'image_url' => $photo->getRealPath(),
                'prompt' => "Анализ внешности для салона красоты. Определи: тип лица, форму бровей, тон кожи, цвет волос. Рекомендуй причёски, окраски, макияжи.",
            ]);

            // 2. Парсим ответ
            $styleProfile = $this->parseBeautyAnalysis($analysis);

            // 3. Получаем рекомендации мастеров
            $recommendations = $this->recommendation->getBeautyMasters(
                styleProfile: $styleProfile,
                userId: $userId,
            );

            // 4. Логирование
            Log::channel('audit')->info('Beauty constructor used', [
                'user_id' => $userId,
                'style_profile' => $styleProfile,
                'masters_count' => count($recommendations),
            ]);

            return [
                'success' => true,
                'style_profile' => $styleProfile,
                'recommended_masters' => $recommendations,
                'ar_link' => url('/beauty/ar-preview/' . $userId),  // AR примерка
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Beauty constructor failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function parseBeautyAnalysis(string $response): array
    {
        // Парсим JSON-ответ от OpenAI
        return [
            'face_type' => 'oval',  // или square, round, heart и т.д.
            'skin_tone' => 'warm',  // или cool, neutral
            'hair_color' => 'brown',
            'brow_shape' => 'arched',
            'recommended_hairstyles' => ['short_pixie', 'long_waves', 'bob'],
            'recommended_colors' => ['warm_brown', 'honey_blonde', 'copper'],
            'recommended_makeup' => ['natural', 'smokey', 'bold'],
        ];
    }
}
```

---

### 2. FURNITURE & INTERIOR — AI Конструктор Интерьера

**Функциональность:**
```
1. Загрузить фото комнаты
2. AI определяет: стиль, палитру, размеры, свет
3. Рекомендует мебель, цвета стен, декор, текстиль
4. 3D-визуализация комнаты с мебелью
5. Получить список товаров с ценами
6. Оценка стоимости переделки / переоснащения
```

**Реализация**
```php
declare(strict_types=1);

namespace App\Domains\Furniture\Services\AI;

final readonly class InteriorDesignConstructorService
{
    public function analyzeRoomAndDesign(
        \Illuminate\Http\UploadedFile $roomPhoto,
        string $designStyle,
        int $budget,
        int $userId,
    ): array {
        try {
            // 1. Анализ комнаты (размеры, свет, существующая мебель)
            $roomAnalysis = $this->analyzeRoom($roomPhoto);

            // 2. Генерировать рекомендации по дизайну
            $recommendations = $this->generateDesignRecommendations(
                roomAnalysis: $roomAnalysis,
                designStyle: $designStyle,
                budget: $budget,
            );

            // 3. Получить товары для дизайна
            $products = $this->recommendation->getFurnitureForDesign(
                recommendations: $recommendations,
                maxPrice: $budget,
                designStyle: $designStyle,
            );

            // 4. 3D-визуализация
            $visualization = $this->generateVisualization($roomAnalysis, $products);

            return [
                'success' => true,
                'room_analysis' => $roomAnalysis,
                'recommendations' => $recommendations,
                'products' => $products,
                'visualization_url' => $visualization,
                'total_cost' => array_sum(array_map(fn($p) => $p['price'] * $p['quantity'], $products)),
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Interior constructor failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function analyzeRoom(\Illuminate\Http\UploadedFile $photo): array
    {
        // Определить: размеры, окна, свет, существующая мебель
        return [
            'size' => '20 m2',
            'lighting' => 'bright',  // или dim, mixed
            'windows' => 2,
            'existing_furniture' => ['sofa', 'table'],
            'wall_color' => 'white',
            'floor_type' => 'parquet',
        ];
    }

    private function generateDesignRecommendations(
        array $roomAnalysis,
        string $designStyle,
        int $budget,
    ): array {
        // Минимализм, скандинавский, лофт, классика, модерн и т.д.
        return [
            'style' => $designStyle,
            'color_palette' => ['white', 'gray', 'wood'],
            'furniture_needed' => ['bed', 'nightstands', 'desk', 'shelves'],
            'estimated_cost' => $budget,
            'lighting_suggestion' => 'pendant_lamps',
            'textiles' => ['curtains', 'carpet', 'bedding'],
        ];
    }

    private function generateVisualization(array $roomAnalysis, array $products): string
    {
        // Использовать Blender или другой 3D сервис для визуализации
        // Возвращает URL на 3D-модель комнаты
        return 'https://visualization.service/interior-' . uniqid();
    }
}
```

---

### 3. FOOD — AI Конструктор Меню / Рецептов

**Функциональность:**
```
1. Выбрать ингредиенты / диету / калории
2. AI генерирует рецепты и блюда
3. Подбор по БЖУ, аллергенам, времени приготовления
4. Получить список доступных блюд в ресторанах
5. Заказать готовое блюдо или ингредиенты для приготовления дома
```

**Реализация**
```php
declare(strict_types=1);

namespace App\Domains\Food\Services\AI;

final readonly class MenuConstructorService
{
    public function generateMenuByDiet(
        array $ingredients,
        string $diet,
        int $minCalories,
        int $maxCalories,
        int $userId,
    ): array {
        try {
            // 1. Генерировать рецепты по ингредиентам
            $recipes = $this->openai->generateRecipes([
                'ingredients' => $ingredients,
                'diet' => $diet,  // vegan, keto, low-carb и т.д.
                'calories_min' => $minCalories,
                'calories_max' => $maxCalories,
            ]);

            // 2. Получить доступные блюда в ресторанах
            $readyDishes = $this->recommendation->getReadyDishes(
                recipes: $recipes,
                userId: $userId,
            );

            // 3. Получить ингредиенты для дома
            $ingredients = $this->recommendation->getIngredientsForHome(
                recipes: $recipes,
                userId: $userId,
            );

            return [
                'success' => true,
                'recipes' => $recipes,
                'ready_dishes' => $readyDishes,
                'home_ingredients' => $ingredients,
                'total_calories' => array_sum(array_map(fn($r) => $r['calories'], $recipes)),
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Menu constructor failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

---

### 4. FASHION/COSMETICS — AI Подбор Стиля

**Функциональность:**
```
1. Загрузить фото пользователя или типа внешности
2. AI определяет: тип контрастности, цветотип, стиль
3. Рекомендует цвета, фасоны, бренды косметики
4. Виртуальная примерка одежды (AR)
5. Получить товары с фильтром по цветотипу и стилю
```

---

### 5. REAL ESTATE — AI Конструктор Дизайна Квартиры

**Функциональность:**
```
1. Загрузить план квартиры
2. AI генерирует варианты дизайна/планировки
3. Рекомендует мебель и отделку
4. 3D-тур по квартире с готовым дизайном
5. Расчёт стоимости ремонта и оборудования
```

---

## 📊 ML АНАЛИЗ ВКУСОВ ПОЛЬЗОВАТЕЛЯ

**Статический анализ, сохраняемый в профиле**

### Таблица UserTasteProfile

```php
Schema::create('user_taste_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->json('favorite_categories');  // Категории товаров
    $table->json('price_range');          // Бюджет (budget, mid, premium, luxury)
    $table->json('favorite_brands');      // Любимые бренды (top 5)
    $table->json('favorite_colors');      // Любимые цвета (для fashion)
    $table->json('favorite_sizes');       // Любимые размеры (для fashion)
    $table->json('dietary_preferences');  // Диетические ограничения (для food)
    $table->json('style_profile');        // Стиль (для beauty/fashion)
    $table->timestamp('analyzed_at');
    $table->timestamps();
});
```

### Сервис анализа

```php
declare(strict_types=1);

namespace App\Services\ML;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UserTasteAnalyzerService
{
    /**
     * Анализировать вкусы пользователя на основе:
     * - История покупок
     * - Просмотры товаров (с временем)
     * - Рейтинги и отзывы
     */
    public function analyzeAndUpdateProfile(int $userId): void
    {
        $user = User::findOrFail($userId);

        // 1. Анализ категорий товаров
        $favoriteCategories = DB::table('product_views')
            ->where('user_id', $userId)
            ->select('product_category', DB::raw('COUNT(*) as count'))
            ->groupBy('product_category')
            ->orderByRaw('count DESC')
            ->limit(5)
            ->pluck('count', 'product_category')
            ->toArray();

        // 2. Анализ ценового диапазона
        $avgPrice = DB::table('orders')
            ->where('user_id', $userId)
            ->avg('total_price');

        $priceRange = match (true) {
            $avgPrice < 1000 => 'budget',
            $avgPrice < 5000 => 'mid',
            $avgPrice < 15000 => 'premium',
            default => 'luxury',
        };

        // 3. Анализ любимых брендов
        $favoritesBrands = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.user_id', $userId)
            ->select('products.brand', DB::raw('COUNT(*) as count'))
            ->groupBy('products.brand')
            ->orderByRaw('count DESC')
            ->limit(5)
            ->pluck('count', 'brand')
            ->toArray();

        // 4. Анализ цветов (для fashion)
        $favoriteColors = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.user_id', $userId)
            ->select('products.color', DB::raw('COUNT(*) as count'))
            ->groupBy('products.color')
            ->orderByRaw('count DESC')
            ->limit(5)
            ->pluck('count', 'color')
            ->toArray();

        // 5. Анализ размеров (для fashion)
        $favoriteSizes = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.user_id', $userId)
            ->select('products.size', DB::raw('COUNT(*) as count'))
            ->groupBy('products.size')
            ->orderByRaw('count DESC')
            ->pluck('count', 'size')
            ->toArray();

        // 6. Сохранить профиль
        $user->taste_profile = [
            'favorite_categories' => $favoriteCategories,
            'price_range' => $priceRange,
            'favorite_brands' => $favoritesBrands,
            'favorite_colors' => $favoriteColors,
            'favorite_sizes' => $favoriteSizes,
            'analyzed_at' => now()->toIso8601String(),
        ];

        $user->save();

        Log::channel('audit')->info('User taste profile updated', [
            'user_id' => $userId,
            'categories' => count($favoriteCategories),
            'price_range' => $priceRange,
        ]);
    }

    /**
     * Получить рекомендации на основе профиля вкусов
     */
    public function getRecommendationsByProfile(int $userId): Collection
    {
        $user = User::findOrFail($userId);
        $profile = $user->taste_profile ?? [];

        $categories = array_keys($profile['favorite_categories'] ?? []);
        $priceRange = $profile['price_range'] ?? 'mid';
        $brands = array_keys($profile['favorite_brands'] ?? []);

        return Product::whereIn('category', $categories)
            ->whereIn('brand', $brands)
            ->when($priceRange === 'budget', fn($q) => $q->where('price', '<', 1000))
            ->when($priceRange === 'mid', fn($q) => $q->whereBetween('price', [1000, 5000]))
            ->when($priceRange === 'premium', fn($q) => $q->whereBetween('price', [5000, 15000]))
            ->when($priceRange === 'luxury', fn($q) => $q->where('price', '>', 15000))
            ->limit(20)
            ->get();
    }
}
```

---

## 📍 ЗАПОМИНАНИЕ АДРЕСОВ И ПОЕЗДОК

**До 5 адресов + история**

### Таблица UserAddresses

```php
Schema::create('user_addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->enum('type', ['home', 'work', 'other'])->default('other');
    $table->string('address');
    $table->decimal('lat', 10, 8)->nullable();
    $table->decimal('lon', 11, 8)->nullable();
    $table->boolean('is_default')->default(false);
    $table->integer('usage_count')->default(0);  // Сколько раз использовался
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'address']);
    $table->index(['user_id', 'usage_count']);
});
```

### Сервис адресов

```php
declare(strict_types=1);

namespace App\Services;

final readonly class UserAddressService
{
    /**
     * Добавить или получить существующий адрес (максимум 5)
     */
    public function addOrGetAddress(
        int $userId,
        string $address,
        string $type = 'other',
        ?float $lat = null,
        ?float $lon = null,
    ): \App\Models\UserAddress {
        // 1. Проверить, существует ли адрес
        $existing = UserAddress::where([
            'user_id' => $userId,
            'address' => $address,
        ])->first();

        if ($existing) {
            $existing->update([
                'usage_count' => $existing->usage_count + 1,
                'last_used_at' => now(),
            ]);
            return $existing;
        }

        // 2. Проверить лимит (максимум 5 адресов)
        $count = UserAddress::where('user_id', $userId)->count();
        if ($count >= 5) {
            // Удалить наименее используемый адрес
            UserAddress::where('user_id', $userId)
                ->orderBy('usage_count')
                ->first()
                ->delete();
        }

        // 3. Создать новый адрес
        return UserAddress::create([
            'user_id' => $userId,
            'address' => $address,
            'type' => $type,
            'lat' => $lat,
            'lon' => $lon,
            'usage_count' => 1,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Получить историю адресов пользователя (топ 5)
     */
    public function getAddressHistory(int $userId): Collection
    {
        return UserAddress::where('user_id', $userId)
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get();
    }
}
```

---

## 💰 AI-КАЛЬКУЛЯТОРЫ ЦЕН И СКИДОК

### Furniture — Расчёт Стоимости Ремонта

```php
declare(strict_types=1);

namespace App\Domains\Furniture\Services\AI;

final readonly class RepairCalculatorService
{
    public function calculateRepairCost(array $items): array
    {
        $baseCost = 0;
        $laborCost = 0;
        $materials = [];

        foreach ($items as $item) {
            // Базовая стоимость товара
            $baseCost += $item['price'] * ($item['quantity'] ?? 1);

            // Трудозатраты (500 ₽ за час)
            $laborCost += ($item['repair_hours'] ?? 0) * 500;

            // Материалы
            $materials[] = [
                'name' => $item['material'],
                'quantity' => $item['quantity'] ?? 1,
                'cost' => $item['material_cost'] ?? 0,
            ];
        }

        $totalMaterialsCost = array_sum(array_map(fn($m) => $m['cost'], $materials));
        $subtotal = $baseCost + $laborCost + $totalMaterialsCost;

        // Скидки по объёму
        $discount = match (true) {
            $baseCost > 50000 => (int)($subtotal * 0.15),
            $baseCost > 20000 => (int)($subtotal * 0.10),
            $baseCost > 10000 => (int)($subtotal * 0.05),
            default => 0,
        };

        return [
            'base_cost' => $baseCost,
            'labor_cost' => $laborCost,
            'materials_cost' => $totalMaterialsCost,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max($subtotal - $discount, 0),
            'materials' => $materials,
        ];
    }
}
```

---

## ✅ ЧЕКЛИСТ РЕАЛИЗАЦИИ AI И ML

- [ ] AI-конструктор для каждой вертикали (5+ вертикалей)
- [ ] UserTasteProfile таблица + анализ
- [ ] Запоминание до 5 адресов (home, work, other)
- [ ] AI-калькуляторы цен для основных вертикалей
- [ ] OpenAI Vision API интеграция
- [ ] 3D-визуализация для Furniture/Interior/RealEstate
- [ ] AR-примерка для Beauty/Fashion/Cosmetics
- [ ] Периодический пересчёт вкусов (раз в неделю)
- [ ] Рекомендации по профилю вкусов
- [ ] Ежегодная анонимизация данных (GDPR/ФЗ-152)

---

**AI-конструкторы и ML-анализ ОБЯЗАТЕЛЬНЫ для Production.**  
**Это критическая функциональность платформы.**
