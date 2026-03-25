<?php

/**
 * USER TASTE PROFILE v2.0 - ПОЛНАЯ ДОКУМЕНТАЦИЯ И ПРИМЕРЫ
 * 
 * Это полная production-ready реализация ML-анализа вкусов пользователя.
 * Используется для персональных рекомендаций, AI-конструкторов и аналитики.
 * 
 * CANON 2026: UTF-8 без BOM, CRLF, declare(strict_types=1)
 * 
 * ============================================================================
 * АРХИТЕКТУРА
 * ============================================================================
 * 
 * Таблицы БД:
 * - user_taste_profiles       Основной профиль вкусов (jsonb с явными и неявными данными)
 * - user_taste_profile_history История версий (для откатов и аналитики)
 * - user_body_metrics         Физические параметры (размеры, BMI, параметры лица)
 * 
 * Сервисы:
 * - UserTasteProfileService   Управление профилями
 * - TasteMLService            ML вычисления (embeddings, скоры, рекомендации)
 * 
 * Jobs:
 * - MLRecalculateUserTastesJob Ежедневный пересчёт всех профилей (03:00 UTC)
 * 
 * AI-конструкторы:
 * - AIBeautyConstructorService  Анализ лица → рекомендации красоты
 * - AIInteriorConstructorService Анализ комнаты → рекомендации интерьера
 * - AIFashionConstructorService  Фото тела → рекомендации одежды
 * 
 * ============================================================================
 * СТРУКТУРА ПРОФИЛЯ (jsonb)
 * ============================================================================
 * 
 * {
 *   "user_id": 123,
 *   "version": 12,
 *   "updated_at": "2026-03-23T18:45:12Z",
 *   "calculated_at": "2026-03-23T03:12:00Z",
 * 
 *   // === ЯВНЫЕ ПРЕДПОЧТЕНИЯ (пользователь сам указывает) ===
 *   "explicit": {
 *     "sizes": {
 *       "clothing": {"top": "M", "bottom": "32", "dress": "42", "confidence": 0.95},
 *       "shoes": {"eu": "39", "us": "8.5", "confidence": 0.98}
 *     },
 *     "body_metrics": {
 *       "height_cm": 172,
 *       "weight_kg": 68,
 *       "body_shape": "hourglass|rectangle|pear|apple|inverted_triangle",
 *       "skin_tone": "warm_beige",
 *       "hair_color": "dark_brown",
 *       "eye_color": "hazel"
 *     },
 *     "dietary": {
 *       "type": ["vegan", "gluten_free"],
 *       "allergies": ["nuts", "shellfish"],
 *       "avoid": ["spicy", "dairy"],
 *       "preferred_cuisines": ["italian", "japanese"]
 *     },
 *     "style_preferences": {
 *       "fashion": ["minimalism", "streetwear", "boho"],
 *       "colors": ["black", "beige", "emerald"],
 *       "brands": ["Zara", "H&M", "Nike"]
 *     },
 *     "lifestyle": {
 *       "activity_level": "moderate",
 *       "interests": ["yoga", "travel", "cooking"],
 *       "values": ["eco_friendly", "ethical_production"]
 *     }
 *   },
 * 
 *   // === НЕЯВНЫЕ ПРЕДПОЧТЕНИЯ (ML вычисляет) ===
 *   "implicit": {
 *     "category_scores": {
 *       "fashion_women": 0.94,
 *       "italian_food": 0.89,
 *       "minimal_interior": 0.91
 *     },
 *     "embeddings": {
 *       "main": [0.023, -0.145, 0.678, ...],     // 768 размерности
 *       "fashion": [0.45, 0.12, -0.67, ...],
 *       "food": [0.78, -0.34, 0.21, ...]
 *     },
 *     "behavioral": {
 *       "avg_session_duration_sec": 184,
 *       "purchase_frequency_days": 14.3,
 *       "avg_cart_value": 2840,
 *       "price_sensitivity": 0.42,
 *       "brand_loyalty_score": 0.81
 *     }
 *   },
 * 
 *   // === ИСТОРИЯ ИЗМЕНЕНИЙ ===
 *   "history": [
 *     {
 *       "date": "2026-03-20",
 *       "actions": 47,
 *       "purchases": 3,
 *       "key_changes": ["increased minimalism score"]
 *     }
 *   ],
 * 
 *   // === МЕТАДАННЫЕ ===
 *   "meta": {
 *     "data_quality_score": 0.93,          // 0–1, надёжность профиля
 *     "last_interaction_at": "2026-03-23T17:20:00Z",
 *     "total_interactions": 1247,
 *     "ml_model_version": "taste-v2.3",
 *     "recommendation_influence": 0.68     // 0–0.7, вес ML в выдаче
 *   }
 * }
 * 
 * ============================================================================
 * ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ
 * ============================================================================
 */

// ===== ПРИМЕР 1: Запись явных предпочтений пользователя =====

// В контроллере при редактировании профиля:
use App\Services\ML\UserTasteProfileService;

$tasteService = app(UserTasteProfileService::class);

// Пользователь указал свои размеры и стиль
$preferences = [
    'sizes' => [
        'clothing' => ['top' => 'S', 'bottom' => '26'],
        'shoes' => ['eu' => '37'],
    ],
    'dietary' => [
        'type' => ['vegan'],
        'allergies' => ['nuts'],
    ],
    'style_preferences' => [
        'fashion' => ['minimalism', 'boho'],
        'colors' => ['white', 'beige', 'black'],
        'brands' => ['Zara', 'H&M'],
    ],
];

$profile = $tasteService->updateExplicitPreferences(
    userId: auth()->id(),
    tenantId: filament()->getTenant()->id,
    preferences: $preferences,
    correlationId: request()->header('X-Correlation-ID'),
);

// ===== ПРИМЕР 2: Запись взаимодействия (просмотр товара) =====

// В Livewire компоненте при просмотре товара:
$this->tasteService->recordInteraction(
    userId: auth()->id(),
    tenantId: filament()->getTenant()->id,
    interactionType: 'product_view',
    details: [
        'product_id' => $product->id,
        'category' => 'fashion_women',
        'vertical' => 'Beauty',
        'duration_seconds' => 45,
    ],
);

// ===== ПРИМЕР 3: Запись покупки =====

// В OrderService после успешной оплаты:
$this->tasteService->recordInteraction(
    userId: $order->user_id,
    tenantId: $order->tenant_id,
    interactionType: 'purchase',
    details: [
        'order_id' => $order->id,
        'amount' => $order->total_price,
        'items' => $order->items->pluck('category'),
    ],
);

// ===== ПРИМЕР 4: Использование AI-конструктора (Beauty) =====

use App\Services\AI\AIBeautyConstructorService;

$beautyConstructor = app(AIBeautyConstructorService::class);

// На странице "AI макияж-конструктор"
$result = $beautyConstructor->analyzeFaceAndRecommend(
    facePhoto: $request->file('face_photo'),
    userId: auth()->id(),
    tenantId: filament()->getTenant()->id,
);

// Результат содержит:
// - face_analysis: {"face_shape": "oval", "skin_type": "oily", ...}
// - recommendations: {
//     "hairstyles": [...],
//     "makeup": {...},
//     "skincare": {...},
//     "colors": [...]
//   }

// ===== ПРИМЕР 5: Получение профиля для рекомендаций =====

use App\Models\UserTasteProfile;

$profile = UserTasteProfile::where('user_id', auth()->id())
    ->where('tenant_id', filament()->getTenant()->id)
    ->first();

// Проверить, готов ли профиль для рекомендаций
if ($profile->isReadyForRecommendations()) {
    // Профиль имеет >=10 взаимодействий и data_quality >= 0.6
    // Получить ML-рекомендации
    $recommendations = RecommendationService::getByTaste(
        profile: $profile,
        limit: 20,
    );
} else if ($profile->isColdStart()) {
    // Новый пользователь, использовать collaborative filtering
    $recommendations = RecommendationService::getColdStart(
        userId: auth()->id(),
        limit: 20,
    );
}

// ===== ПРИМЕР 6: Отключить персональные рекомендации =====

$tasteService->setPersonalizationEnabled(
    userId: auth()->id(),
    tenantId: filament()->getTenant()->id,
    enabled: false,  // Пользователь отключил рекомендации
);

// Профиль остаётся, но allow_personalization = false
// RecommendationService проверит флаг и покажет статические рекомендации

// ===== ПРИМЕР 7: Проверить качество профиля =====

$dataQuality = $profile->getDataQualityScore();        // 0–1
$recommendations = $profile->getRecommendationInfluence(); // 0–0.7
$categoryScores = $profile->getCategoryScores();       // ['fashion' => 0.94, ...]
$embedding = $profile->getMainEmbedding();             // [0.023, -0.145, ...]

// Использовать data_quality для UI:
// < 0.3: "Профиль заполняется..." (показать форму)
// 0.3–0.6: "Узнаём ваши вкусы..." (показать рекомендации + форма)
// >= 0.6: "Персонализированные рекомендации" (включить ML)

// ===== ПРИМЕР 8: Ежедневный пересчёт (Job) =====

// В kernel.php (schedule):
$schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)
    ->dailyAt('03:00')  // 03:00 UTC
    ->timezone('UTC');

// Job автоматически:
// 1. Получит всех активных пользователей
// 2. Вычислит новые category_scores
// 3. Обновит embeddings
// 4. Пересчитает data_quality_score
// 5. Создаст запись в user_taste_profile_history

// ===== ПРИМЕР 9: История изменений и откат =====

$histories = $profile->histories()->latest()->limit(5)->get();

foreach ($histories as $history) {
    echo "Date: {$history->created_at}";
    echo "Trigger: {$history->trigger_reason}";
    echo "Version: {$history->version}";
    echo "Changes: " . json_encode($history->changes);
}

// Откат на версию 10:
$oldProfile = UserTasteProfileHistory::where('taste_profile_id', $profile->id)
    ->where('version', 10)
    ->first();

$profile->update($oldProfile->changes);

// ===== ПРИМЕР 10: Body Metrics для AI-конструкторов =====

use App\Models\UserBodyMetrics;

$bodyMetrics = UserBodyMetrics::where('user_id', auth()->id())->first();

// AI-конструктор может использовать:
$bmi = $bodyMetrics->getBMI();            // 18.5–25.0 = normal
$bmiCategory = $bodyMetrics->getBMICategory(); // normal|overweight|obese
$sizeProfile = $bodyMetrics->getSizeProfile(); // ['clothing' => [...], 'shoes' => [...]]

// Для подбора одежды AI берёт:
// - height_cm, weight_kg, body_shape (hourglass/rectangle/pear)
// - size profile (EU/US размеры)
// - рекомендует правильные размеры и силуэты

// ============================================================================
// СЦЕНАРИИ ИСПОЛЬЗОВАНИЯ
// ============================================================================

/*
 * 1. ХОЛ ОДНЫЙ СТАРТ (Новый пользователь, <5 взаимодействий)
 *    - Используется collaborative filtering (похожие пользователи)
 *    - Показывается форма "Расскажи о себе" (явные предпочтения)
 *    - После 5+ действий → перейти на ML-рекомендации
 *
 * 2. ТЁПЛЫЙ СТАРТ (5–10 взаимодействий)
 *    - data_quality_score = 0.3–0.5
 *    - Гибридные рекомендации: 50% ML + 50% популярное
 *    - Продолжать собирать явные предпочтения
 *
 * 3. АКТИВНЫЙ ПОЛЬЗОВАТЕЛЬ (>100 взаимодействий)
 *    - data_quality_score = 0.7–1.0
 *    - ML-рекомендации: 60–70% от выдачи
 *    - Остальное: новинки (20%), акции (10%)
 *
 * 4. ОТКЛЮЧЕНЫ РЕКОМЕНДАЦИИ
 *    - allow_personalization = false
 *    - Показывать только популярное + акции
 *    - Профиль не удаляется, анализ продолжается
 *
 * 5. AI-КОНСТРУКТОР BEAUTY
 *    - Пользователь загружает фото лица
 *    - Vision API анализирует: форма лица, тон кожи, тип волос
 *    - Объединяется с явными предпочтениями (стиль, бренды, цвета)
 *    - Выдаёт: рекомендации причёсок, макияжа, ухода, цветов
 */

// ============================================================================
// ПРОИЗВОДИТЕЛЬНОСТЬ И ОПТИМИЗАЦИЯ
// ============================================================================

/*
 * Кэширование:
 * - Embeddings кэшируются в Redis (TTL 24 часа)
 * - Category scores кэшируются в Redis (TTL 1 час)
 * - Профиль полностью загружается в памяти (jsonb < 100 KB)
 * 
 * Индексы БД:
 * - user_taste_profiles: (tenant_id, user_id) PRIMARY
 * - user_taste_profiles: (tenant_id, updated_at) для batch updates
 * - user_taste_profile_history: (user_id, created_at) для истории
 * - user_body_metrics: (user_id) UNIQUE
 * 
 * Job parallelization:
 * - MLRecalculateUserTastesJob обрабатывает батчами по 100 юзеров
 * - Каждый юзер в отдельной транзакции (isolation level = READ COMMITTED)
 * - Retry 3 раза при ошибке
 * 
 * Размер данных:
 * - Embedding (768 float32): 3 KB
 * - Category scores (50 категорий): 1 KB
 * - Behavioral metrics: 0.5 KB
 * - Всё вместе: ~20 KB на профиль
 */

// ============================================================================
// БЕЗОПАСНОСТЬ И СООТВЕТСТВИЕ CANON 2026
// ============================================================================

/*
 * ✅ UTF-8 без BOM, CRLF
 * ✅ declare(strict_types=1) в начале каждого файла
 * ✅ final readonly properties в сервисах
 * ✅ correlation_id везде (логирование, события, ответы)
 * ✅ tenant_id scoping (все запросы с where('tenant_id', ...))
 * ✅ DB::transaction() для всех мутаций
 * ✅ Log::channel('audit') для всех действий
 * ✅ FraudControlService::check() перед хранением явных данных
 * ✅ Все исключения логируются с полным стек-трейсом
 * ✅ Запрещено return null без исключения
 * ✅ GDPR-compliant: пользователь может удалить профиль
 */
