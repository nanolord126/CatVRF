<?php

/**
 * CANON 2026: User Taste ML Analysis - Quick Integration Guide
 * Быстрый гайд по интеграции ML-анализа вкусов в проект CatVRF
 */

// ============================================================================
// ШАГ 1: Регистрация Event Listener
// ============================================================================

// В app/Providers/EventServiceProvider.php добавить:

use App\Domains\Common\Events\UserInteractionEvent;
use App\Domains\Common\Listeners\UpdateUserTasteProfileListener;

protected $listen = [
    UserInteractionEvent::class => [
        UpdateUserTasteProfileListener::class,
    ],
    // ... другие события ...
];

// ============================================================================
// ШАГ 2: Регистрация Job в Schedule
// ============================================================================

// В app/Console/Kernel.php в методе schedule() добавить:

use App\Domains\Common\Jobs\MLRecalculateUserTastesJob;

protected function schedule(Schedule $schedule): void
{
    // ... другие jobs ...

    $schedule->job(new MLRecalculateUserTastesJob())
        ->dailyAt('04:30')
        ->timezone('UTC')
        ->withoutOverlapping(timeout: 600);
}

// ============================================================================
// ШАГ 3: Запустить миграции
// ============================================================================

// php artisan migrate

// ============================================================================
// ШАГ 4: Интеграция в контроллеры (примеры)
// ============================================================================

// ─────────────────────────────────────────────────────────────────────────
// ПРИМЕР 1: Отправка события при просмотре товара
// ─────────────────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Products;

use App\Domains\Common\Events\UserInteractionEvent;
use App\Models\Product;

final class ProductShowController
{
    public function __invoke(Product $product)
    {
        // Отправить событие о просмотре
        UserInteractionEvent::dispatch(
            userId: auth()->id() ?? null,
            tenantId: tenant()->id,
            interactionType: 'view',
            data: [
                'product_id' => $product->id,
                'vertical' => $product->vertical,
                'category' => $product->category,
                'price' => $product->price,
                'rating' => $product->rating ?? 0,
            ],
            ipAddress: request()->ip() ?? '',
            userAgent: request()->userAgent() ?? '',
        );

        return view('products.show', ['product' => $product]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// ПРИМЕР 2: Отправка события при добавлении в корзину
// ─────────────────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Cart;

use App\Domains\Common\Events\UserInteractionEvent;

final class AddToCartController
{
    public function __invoke()
    {
        $productId = request()->input('product_id');
        $product = Product::findOrFail($productId);

        // Добавить в корзину...

        // Отправить событие
        UserInteractionEvent::dispatch(
            userId: auth()->id(),
            tenantId: tenant()->id,
            interactionType: 'cart_add',
            data: [
                'product_id' => $product->id,
                'vertical' => $product->vertical,
                'price' => $product->price,
                'quantity' => request()->input('quantity', 1),
            ],
        );

        return response()->json(['success' => true]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// ПРИМЕР 3: Получение рекомендаций и показ пользователю
// ─────────────────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Recommendations;

use App\Domains\Common\Services\TasteMLService;
use App\Domains\Common\Services\UserTasteProfileService;

final class RecommendationsController
{
    public function __construct(
        private TasteMLService $mlService,
        private UserTasteProfileService $tasteService,
    ) {}

    public function index()
    {
        $userId = auth()->id();
        $tenantId = tenant()->id;

        // Проверить, включена ли персонализация для пользователя
        if (!$this->tasteService->isPersonalizationEnabled($userId, $tenantId)) {
            // Показать популярные товары вместо рекомендаций
            return view('recommendations.popular');
        }

        // Получить ML-рекомендации
        $recommendations = $this->mlService->getRecommendationsForUser(
            userId: $userId,
            tenantId: $tenantId,
            vertical: request()->input('vertical', ''),
            limit: 20,
        );

        // Если рекомендаций недостаточно, добавить популярные
        if (count($recommendations) < 20) {
            $popular = Product::popular()
                ->limit(20 - count($recommendations))
                ->get();

            foreach ($popular as $product) {
                $recommendations[] = [
                    'product_id' => $product->id,
                    'score' => 0.5, // Популярные товары
                    'vertical' => $product->vertical,
                ];
            }
        }

        return view('recommendations.index', [
            'recommendations' => $recommendations,
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// ПРИМЕР 4: Установка размеров пользователя (в профиле)
// ─────────────────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Profile;

use App\Domains\Common\Services\UserTasteProfileService;

final class UpdateSizesController
{
    public function __construct(
        private UserTasteProfileService $tasteService,
    ) {}

    public function __invoke()
    {
        $validated = request()->validate([
            'clothing_size' => 'required|in:XS,S,M,L,XL,XXL',
            'shoe_size' => 'required|integer|between:30,48',
            'jeans_size' => 'required|integer|between:24,40',
        ]);

        $this->tasteService->setSizeProfile(
            userId: auth()->id(),
            tenantId: tenant()->id,
            sizes: [
                'clothing' => $validated['clothing_size'],
                'shoes' => (int) $validated['shoe_size'],
                'jeans' => (int) $validated['jeans_size'],
            ],
        );

        return response()->json(['success' => true]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// ПРИМЕР 5: Beauty AI Constructor
// ─────────────────────────────────────────────────────────────────────────

namespace App\Http\Controllers\Beauty;

use App\Domains\Beauty\Services\BeautyAIConstructorService;

final class BeautyAIConstructorController
{
    public function __construct(
        private BeautyAIConstructorService $constructor,
    ) {}

    public function analyze()
    {
        $validated = request()->validate([
            'face_photo' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $result = $this->constructor->analyzePhotoAndRecommend(
            photo: $validated['face_photo'],
            userId: auth()->id(),
            tenantId: tenant()->id(),
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }
}

// ============================================================================
// ШАГ 5: Конфигурация .env
// ============================================================================

// TASTE_EMBEDDING_DIMENSION=384
// TASTE_EMBEDDING_PROVIDER=sentencetransformers
// TASTE_CACHE_STORE=redis
// TASTE_REALTIME_UPDATES=false

// ============================================================================
// ШАГ 6: Регистрация сервис-провайдера
// ============================================================================

// В config/app.php добавить в providers:

App\Providers\TasteMLServiceProvider::class,

// Или создать провайдер:

namespace App\Providers;

use App\Domains\Common\Services\TasteMLService;
use App\Domains\Common\Services\UserTasteProfileService;
use Illuminate\Support\ServiceProvider;

final class TasteMLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserTasteProfileService::class, function () {
            return new UserTasteProfileService(
                resolve(TasteMLService::class),
            );
        });

        $this->app->singleton(TasteMLService::class);
    }
}

// ============================================================================
// ШАГ 7: Мониторинг и отладка
// ============================================================================

// Проверить логи:
// tail -f storage/logs/audit.log | grep "taste"

// Запустить job вручную:
// php artisan taste-ml:recalculate --verbose

// Проверить профиль пользователя:
// php artisan tinker
// $profile = \App\Models\UserTasteProfile::where('user_id', 1)->first();
// $profile->implicit_score

// ============================================================================
// ШАГ 8: Интеграция с Filament (опционально)
// ============================================================================

// Создать Filament Page для просмотра профилей:

namespace App\Filament\Tenant\Pages;

use App\Models\UserTasteProfile;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

final class ViewUserTasteProfiles extends Page implements HasTable
{
    use InteractsWithTable;

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->query(UserTasteProfile::query())
            ->columns([
                TextColumn::make('user_id')->label('User ID'),
                TextColumn::make('version')->label('Version'),
                TextColumn::make('interaction_count')->label('Interactions'),
                TextColumn::make('ctr')->label('CTR'),
                TextColumn::make('last_calculated_at')->dateTime(),
            ]);
    }
}

// ============================================================================
// CHECKLIST: Что нужно сделать для полной интеграции
// ============================================================================

// ✓ Запустить миграцию: php artisan migrate
// ✓ Создать ProductEmbedding для всех товаров (см. другой скрипт)
// ✓ Зарегистрировать Event Listener в EventServiceProvider
// ✓ Добавить Job в Schedule в Kernel.php
// ✓ Обновить контроллеры для отправки UserInteractionEvent
// ✓ Добавить конфиг taste_ml.php
// ✓ Настроить .env переменные
// ✓ Запустить тесты: php artisan test tests/Feature/UserTasteML
// ✓ Включить очередь: php artisan queue:work
// ✓ Создать первые embeddings для товаров
// ✓ Начать собирать взаимодействия (автоматически)
// ✓ Дождаться первого расчёта (04:30 UTC)
// ✓ Проверить логи и метрики

// ============================================================================
// ВАЖНЫЕ КОНСТАНТЫ CANON 2026
// ============================================================================

// Все операции должны иметь correlation_id
// Все операции должны быть в DB::transaction()
// Все важные события должны логироваться в Log::channel('audit')
// Все API endpoints должны быть rate-limited
// FraudMLService::check() перед рекомендациями (если требуется)
// User персональные данные ДОЛЖНЫ быть анонимизированы для ML-обучения

// ============================================================================
// ПОТЕНЦИАЛЬНЫЕ ПРОБЛЕМЫ И РЕШЕНИЯ
// ============================================================================

// Проблема: Job не запускается
// Решение: Проверить, запущена ли очередь (php artisan queue:work)

// Проблема: Embeddings не вычисляются
// Решение: Проверить, установлена ли SentenceTransformers (pip install sentence-transformers)

// Проблема: Low CTR (< 5%)
// Решение: Снизить вес ML в конфиге taste_ml.php weights

// Проблема: Памяти не хватает
// Решение: Уменьшить batch_size с 1000 на 500 в taste_ml.php

// ============================================================================
// ДОПОЛНИТЕЛЬНЫЕ РЕСУРСЫ
// ============================================================================

// Документация: TASTE_ML_ANALYSIS_DOCUMENTATION.md
// Конфиг: config/taste_ml.php
// Тесты: tests/Feature/UserTasteML/UserTasteMLTest.php
// Service Providers: app/Providers/TasteMLServiceProvider.php
?>
