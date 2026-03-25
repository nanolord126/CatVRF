<?php

/**
 * TASTE PROFILE v2.0 - SETUP INSTRUCTIONS
 * 
 * Файл с инструкциями по интеграции в проект
 * 
 * ============================================================================
 * ШАГ 1: Запуск миграций
 * ============================================================================
 * 
 * php artisan migrate
 * 
 * Создаст таблицы:
 * - user_taste_profiles
 * - user_taste_profile_history
 * - user_body_metrics
 * 
 * ============================================================================
 * ШАГ 2: Опубликовать конфиг
 * ============================================================================
 * 
 * php artisan vendor:publish --provider="App\Providers\TasteMLServiceProvider"
 * 
 * Скопирует config/taste-ml.php в проект
 * 
 * ============================================================================
 * ШАГ 3: Добавить Job в Kernel
 * ============================================================================
 * 
 * В app/Console/Kernel.php:
 * 
 * protected function schedule(Schedule $schedule)
 * {
 *     // Ежедневный пересчёт ML-профилей в 03:00 UTC
 *     $schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)
 *         ->dailyAt('03:00')
 *         ->timezone('UTC')
 *         ->onOneServer();  // Только один сервер в кластере
 * }
 * 
 * ============================================================================
 * ШАГ 4: Создать TasteMLService (если не существует)
 * ============================================================================
 * 
 * Файл: app/Services/ML/TasteMLService.php
 * 
 * Содержит:
 * - calculateCategoryScores()      Вычисление ML-скоров по категориям
 * - calculateBehavioralMetrics()   Behavioral анализ (цена чув., лояльность)
 * - generateEmbeddings()           Генерация многоуровневых embeddings
 * - getCurrentModelVersion()       Текущая версия модели
 * 
 * ============================================================================
 * ШАГ 5: Интегрировать в Livewire компоненты
 * ============================================================================
 * 
 * В любом Livewire компоненте можно использовать:
 * 
 * use App\Services\ML\UserTasteProfileService;
 * 
 * class ProductCard extends Component
 * {
 *     public function __construct(
 *         private readonly UserTasteProfileService $tasteService
 *     ) {}
 * 
 *     public function mount()
 *     {
 *         // Записать просмотр товара
 *         $this->tasteService->recordInteraction(
 *             userId: auth()->id(),
 *             tenantId: filament()->getTenant()->id,
 *             interactionType: 'product_view',
 *             details: ['product_id' => $this->product->id],
 *         );
 *     }
 * }
 * 
 * ============================================================================
 * ШАГ 6: Сервис-провайдер
 * ============================================================================
 * 
 * В app/Providers/AppServiceProvider.php:
 * 
 * public function register(): void
 * {
 *     $this->app->singleton(UserTasteProfileService::class, function ($app) {
 *         return new UserTasteProfileService(
 *             new TasteMLService(),
 *         );
 *     });
 * 
 *     $this->app->singleton(AIBeautyConstructorService::class, function ($app) {
 *         return new AIBeautyConstructorService(
 *             $app->make(UserTasteProfileService::class),
 *             $app->make(\OpenAI\Client::class),
 *         );
 *     });
 * }
 * 
 * ============================================================================
 * ШАГ 7: Переменные окружения
 * ============================================================================
 * 
 * .env файл:
 * 
 * # Embeddings
 * TASTE_EMBEDDINGS_MODEL=text-embedding-3-large
 * TASTE_EMBEDDINGS_DIMENSIONS=768
 * TASTE_EMBEDDINGS_PROVIDER=openai
 * 
 * # ML Model
 * TASTE_MODEL_VERSION=taste-v2.3-20260325
 * TASTE_MODEL_AUTO_RETRAIN=true
 * 
 * # Caching
 * TASTE_CACHE_ENABLED=true
 * 
 * # AI Constructors
 * AI_BEAUTY_CONSTRUCTOR_ENABLED=true
 * AI_INTERIOR_CONSTRUCTOR_ENABLED=true
 * AI_FASHION_CONSTRUCTOR_ENABLED=true
 * 
 * # Debug
 * TASTE_DEBUG=false
 * 
 * ============================================================================
 * ШАГ 8: Проверка работы
 * ============================================================================
 * 
 * Тестировать можно:
 * 
 * // 1. Создание профиля
 * $tasteService = app(UserTasteProfileService::class);
 * $profile = $tasteService->getOrCreateProfile(123, 1);
 * 
 * // 2. Обновление явных предпочтений
 * $tasteService->updateExplicitPreferences(
 *     123, 1,
 *     ['sizes' => ['clothing' => ['top' => 'M']]]
 * );
 * 
 * // 3. Запись взаимодействия
 * $tasteService->recordInteraction(
 *     123, 1,
 *     'product_view',
 *     ['product_id' => 456]
 * );
 * 
 * // 4. Проверка профиля
 * $profile->refresh();
 * dd($profile->getDataQualityScore());
 * dd($profile->getCategoryScores());
 * 
 * ============================================================================
 * ШАГ 9: Использование в рекомендациях
 * ============================================================================
 * 
 * В RecommendationService:
 * 
 * public function getRecommendations(int $userId, ?string $vertical = null)
 * {
 *     $profile = UserTasteProfile::where('user_id', $userId)->first();
 * 
 *     if (!$profile || !$profile->allow_personalization) {
 *         // Fallback на популярное
 *         return $this->getPopularItems($vertical);
 *     }
 * 
 *     $influence = $profile->getRecommendationInfluence();  // 0–0.7
 * 
 *     $mlRecommendations = $this->getByMLScores($profile);
 *     $popular = $this->getPopularItems($vertical);
 * 
 *     // Гибридные рекомендации
 *     return array_merge(
 *         array_slice($mlRecommendations, 0, (int)($limit * $influence)),
 *         array_slice($popular, 0, (int)($limit * (1 - $influence)))
 *     );
 * }
 * 
 * ============================================================================
 * ШАГ 10: Мониторинг качества
 * ============================================================================
 * 
 * Ежемесячный отчёт:
 * 
 * // Сколько пользователей имеют готовые профили?
 * $readyCount = UserTasteProfile::where('allow_personalization', true)
 *     ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.data_quality_score")) >= 0.6')
 *     ->count();
 * 
 * // Средний data_quality_score
 * $avgQuality = DB::raw(
 *     'AVG(JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.data_quality_score")))'
 * );
 * 
 * // CTR персональных рекомендаций
 * // (сравнить click-through rate ML vs популярного)
 * 
 * ============================================================================
 * TROUBLESHOOTING
 * ============================================================================
 * 
 * Проблема: "Class TasteMLService not found"
 * Решение: Создать app/Services/ML/TasteMLService.php
 * 
 * Проблема: "OpenAI\Client not found"
 * Решение: composer require openai/php-client
 * 
 * Проблема: Job не запускается
 * Решение: Проверить очередь - php artisan queue:work
 * 
 * Проблема: Embeddings слишком большие
 * Решение: Использовать text-embedding-3-small (384 dims вместо 768)
 * 
 * ============================================================================
 * ДОКУМЕНТАЦИЯ
 * ============================================================================
 * 
 * Полная документация: TASTE_PROFILE_V2_DOCUMENTATION.php
 * Примеры использования: см. там же
 * API: UserTasteProfileService методы
 * 
 * ============================================================================
 */

echo "USER TASTE PROFILE v2.0 - SETUP READY

Миграции: ✓ database/migrations/2026_03_25_000001_create_user_taste_profiles_table.php
Модели:
  ✓ app/Models/UserTasteProfile.php
  ✓ app/Models/UserTasteProfileHistory.php
  ✓ app/Models/UserBodyMetrics.php

Сервисы:
  ✓ app/Services/ML/UserTasteProfileService.php
  ⚠ app/Services/ML/TasteMLService.php (нужно создать)

Jobs:
  ✓ app/Jobs/ML/MLRecalculateUserTastesJob.php

AI-конструкторы:
  ✓ app/Services/AI/AIBeautyConstructorService.php

Конфиги:
  ✓ config/taste-ml.php

Документация:
  ✓ TASTE_PROFILE_V2_DOCUMENTATION.php

Далее:
  1. Создать TasteMLService
  2. Добавить Job в Kernel
  3. Запустить миграции: php artisan migrate
  4. Проверить работу
";
