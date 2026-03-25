# CANON 2026: User Taste ML Analysis - Production-Ready Implementation

**Дата:** 25 марта 2026  
**Статус:** PRODUCTION READY  
**Версия:** 1.0

---

## 1. Обзор системы

Система ML-анализа вкусов пользователя (User Taste Profile ML Analysis) предназначена для:

- **Персонализации** поиска и рекомендаций на основе явных и неявных предпочтений
- **AI-конструкторов** (Бьюти, Интерьер, Подбор одежды и т.д.)
- **Увеличения конверсии** через релевантную выдачу
- **Анонимизированного анализа** (данные сохраняются даже при opt-out)

---

## 2. Архитектурные компоненты

### 2.1 Модели данных

```
UserTasteProfile (таблица)
├── id, uuid, tenant_id, user_id
├── embedding (384 или 768-мерный вектор)
├── explicit_preferences (явные предпочтения: размеры, бренды, диеты)
├── implicit_score (неявные оценки категорий: fashion: 0.92)
├── size_profile (размеры одежды, обуви)
├── favorite_brands (любимые бренды с весами)
├── style_preferences (стилистические предпочтения)
├── color_preferences (цветовые предпочтения)
├── interaction_history (последние 100 взаимодействий)
├── is_enabled, opt_out, version
└── timestamps

ProductEmbedding (таблица)
├── id, product_id, vertical
├── embedding (384 или 768-мерный вектор товара)
└── similarity_cache (при необходимости)

UserInteraction (events)
├── view (просмотр товара)
├── cart_add (добавление в корзину)
├── cart_remove (удаление из корзины)
├── purchase (покупка)
├── review (отзыв)
├── rating (оценка)
├── like (лайк/сердце/огонь)
└── wishlist_add (добавление в вишлист)
```

### 2.2 Сервисы

```
UserTasteProfileService
├── getOrCreateProfile(userId, tenantId)
├── updateProfileFromInteraction(userId, tenantId, type, data)
├── getExplicitPreferences(userId, tenantId) → array
├── getImplicitScores(userId, tenantId) → array
├── setSizeProfile(userId, tenantId, sizes)
├── setExplicitPreferences(userId, tenantId, prefs)
├── disablePersonalization(userId, tenantId)
├── enablePersonalization(userId, tenantId)
├── isPersonalizationEnabled(userId, tenantId) → bool
└── getProfileStats(userId, tenantId) → array

TasteMLService
├── cosineSimilarity(vectorA, vectorB) → float
├── getRecommendationsForUser(userId, tenantId, vertical, limit) → array
├── recalculateProfileEmbedding(userId, tenantId, correlationId) → bool
├── updateCTR(userId, tenantId, ctr)
└── updateAcceptanceRate(userId, tenantId, rate)
```

### 2.3 Events & Listeners

```
UserInteractionEvent
├── userId, tenantId, interactionType
├── data (product_id, vertical, category, price, rating, duration_seconds)
└── correlationId

UpdateUserTasteProfileListener (queued)
├── Обновляет interaction_history
├── Пересчитывает implicit_score
├── Сохраняет явные предпочтения (размеры, бренды)
└── Логирует в audit channel
```

### 2.4 Jobs

```
MLRecalculateUserTastesJob (ежедневно в 04:30 UTC)
├── Получает всех пользователей с interaction_count > 0
├── Пересчитывает embeddings на основе interaction_history
├── Обновляет version профиля
├── Сохраняет корреляцию для аудита
└── Логирует статистику обработки
```

---

## 3. Как использовать

### 3.1 Сбор данных о взаимодействиях

В контроллере при просмотре товара:

```php
<?php

namespace App\Http\Controllers\Products;

use App\Domains\Common\Events\UserInteractionEvent;

final class ProductController
{
    public function show(int $id)
    {
        $product = Product::findOrFail($id);
        
        // Отправить событие о просмотре
        UserInteractionEvent::dispatch(
            userId: auth()->id(),
            tenantId: tenant()->id,
            interactionType: 'view',
            data: [
                'product_id' => $product->id,
                'vertical' => $product->vertical,
                'category' => $product->category,
                'price' => $product->price,
                'rating' => $product->rating,
                'duration_seconds' => 0, // будет обновлено на фронте
            ],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        );

        return view('products.show', ['product' => $product]);
    }
}
```

### 3.2 Сбор явных предпочтений

При заполнении профиля пользователя:

```php
<?php

use App\Domains\Common\Services\UserTasteProfileService;

$tasteService = resolve(UserTasteProfileService::class);

// Установить размеры
$tasteService->setSizeProfile(
    userId: auth()->id(),
    tenantId: tenant()->id,
    sizes: [
        'clothing' => 'M',
        'shoes' => '38',
        'jeans' => '30',
    ],
    correlationId: Str::uuid()->toString(),
);

// Установить явные предпочтения
$tasteService->setExplicitPreferences(
    userId: auth()->id(),
    tenantId: tenant()->id,
    preferences: [
        'favorite_brands' => ['Nike', 'Zara', 'Lush'],
        'diet_restrictions' => ['vegetarian', 'no_dairy'],
        'allergies' => ['peanuts', 'shellfish'],
        'style_preference' => 'minimalism',
        'color_palette' => ['black', 'white', 'navy'],
    ],
    correlationId: Str::uuid()->toString(),
);
```

### 3.3 Получение рекомендаций

```php
<?php

use App\Domains\Common\Services\TasteMLService;

$mlService = resolve(TasteMLService::class);

// Получить рекомендации для пользователя
$recommendations = $mlService->getRecommendationsForUser(
    userId: auth()->id(),
    tenantId: tenant()->id,
    vertical: 'Fashion',
    limit: 20,
);

// $recommendations = [
//     ['product_id' => 123, 'score' => 0.92, 'vertical' => 'Fashion'],
//     ['product_id' => 456, 'score' => 0.87, 'vertical' => 'Fashion'],
//     ...
// ]
```

### 3.4 Управление персонализацией

```php
<?php

use App\Domains\Common\Services\UserTasteProfileService;

$tasteService = resolve(UserTasteProfileService::class);

// Отключить рекомендации (но анализ продолжается)
$tasteService->disablePersonalization(
    userId: auth()->id(),
    tenantId: tenant()->id,
);

// Включить обратно
$tasteService->enablePersonalization(
    userId: auth()->id(),
    tenantId: tenant()->id,
);

// Проверить статус
if ($tasteService->isPersonalizationEnabled(auth()->id(), tenant()->id)) {
    // Показать персональные рекомендации
}
```

### 3.5 Beauty AI Constructor

```php
<?php

use App\Domains\Beauty\Services\BeautyAIConstructorService;

$constructor = resolve(BeautyAIConstructorService::class);

$photo = request()->file('face_photo');

$result = $constructor->analyzePhotoAndRecommend(
    photo: $photo,
    userId: auth()->id(),
    tenantId: tenant()->id(),
    correlationId: request()->get('correlation_id', ''),
);

// $result = [
//     'success' => true,
//     'face_analysis' => [
//         'face_shape' => 'oval',
//         'skin_tone' => 'warm',
//         'eye_color' => 'brown',
//         'hair_type' => 'wavy',
//         ...
//     ],
//     'hairstyles' => [
//         ['style' => 'long_layers', 'description' => '...', 'match_score' => 0.92],
//         ...
//     ],
//     'makeup' => [...],
//     'skincare' => [...],
// ]
```

---

## 4. Гибридные рекомендации (40/30/20/10)

```
40% — ML вкусы (cosine similarity с embedding'ом пользователя)
30% — Популярное в его гео/возрасте/демографии
20% — Новинки (для разнообразия)
10% — Акционные товары
```

**Важно:**
- Если пользователь часто НЕ кликает по ML-рекомендациям → снижаем вес ML до 25%
- Максимальное влияние ML = 70% от выдачи
- Если CTR < 5% → отключаем ML и используем популярные товары

---

## 5. Расписание Jobs

```
Каждый день в 04:30 UTC:
  MLRecalculateUserTastesJob
  ├── Обрабатывает до 1000 пользователей
  ├── Пересчитывает embeddings
  └── Логирует статистику

Каждую неделю в понедельник 08:00 UTC:
  GenerateAIRecommendationReportsJob
  ├── Рассчитывает CTR, acceptance_rate, revenue_lift
  ├── Обновляет метрики качества
  └── Отправляет отчёты admin'ам
```

---

## 6. Мониторинг и алерты

### 6.1 Ключевые метрики

```
CTR (Click-Through Rate)
├── Цель: > 8%
├── Алерт: < 5% за день
└── Действие: Снизить вес ML до 25%

Acceptance Rate
├── Цель: > 20% покупок из рекомендаций
├── Алерт: < 10% за неделю
└── Действие: Пересмотреть модель

Embedding Quality (Cosine Similarity)
├── Цель: средний similarity > 0.65
├── Алерт: < 0.55
└── Действие: Пересчитать embeddings

Version Updates
├── Цель: каждый день новые версии профилей
├── Алерт: профиль не обновлялся 7 дней
└── Действие: Пересчитать вручную или удалить
```

### 6.2 Sentry алерты

```
- RecalculationFailure: > 100 ошибок за день
- LowCTR: CTR < 5% за сутки
- QualityDegradation: similarity упал > 15% за неделю
- DataQuality: > 20% взаимодействий без embedding'а
```

---

## 7. GDPR & ФЗ-152 Соответствие

```
✅ Данные анонимизируются для обучения моделей
✅ Пользователь может отключить рекомендации (opt_out)
✅ Нет сохранения сырых персональных данных в embeddings
✅ Все операции логируются с correlation_id (для аудита)
✅ Данные хранятся по region (tenant_id scoping)
✅ Забывание (GDPR right to be forgotten) = удаление профиля + всех взаимодействий
```

---

## 8. Безопасность (CANON 2026)

```
✅ FraudMLService::checkRecommendation() перед выдачей
✅ RateLimiter: 100 запросов/мин на пользователя
✅ Все операции в DB::transaction()
✅ Audit-лог с correlation_id для каждой операции
✅ Embeddings НЕ содержат PII (Personally Identifiable Information)
✅ Доступ к профилям только через tenant scoping
```

---

## 9. Файлы в проекте

```
database/migrations/
  2026_03_25_000001_create_user_taste_profiles_table.php ✅

app/Models/
  UserTasteProfile.php ✅
  ProductEmbedding.php ✅

app/Domains/Common/Services/
  UserTasteProfileService.php ✅
  TasteMLService.php ✅

app/Domains/Common/Events/
  UserInteractionEvent.php ✅

app/Domains/Common/Listeners/
  UpdateUserTasteProfileListener.php ✅

app/Domains/Common/Jobs/
  MLRecalculateUserTastesJob.php ✅

app/Domains/Beauty/Services/
  BeautyAIConstructorService.php ✅ (пример)

app/Domains/{Vertical}/Services/
  {Vertical}AIConstructorService.php (расширяется для каждой вертикали)
```

---

## 10. Примеры интеграции по вертикалям

### 10.1 Мебель и интерьер (Furniture AI Constructor)

```php
<?php

// Загрузить фото комнаты → получить рекомендации мебели
$constructor = resolve(FurnitureAIConstructorService::class);

$result = $constructor->analyzeRoomPhotoAndRecommend(
    photo: request()->file('room_photo'),
    userId: auth()->id(),
    tenantId: tenant()->id,
);

// Возвращает: стиль комнаты, цветовую палитру, рекомендации мебели с ценами
```

### 10.2 Fashion & Cosmetics (Style AI Constructor)

```php
<?php

// Загрузить фото → получить рекомендации одежды/косметики
$constructor = resolve(StyleAIConstructorService::class);

$result = $constructor->analyzePhotoAndRecommendStyle(
    photo: request()->file('photo'),
    userId: auth()->id(),
    tenantId: tenant()->id,
);

// Возвращает: тип внешности, цветовой анализ, рекомендации товаров
```

### 10.3 Food (Menu AI Constructor)

```php
<?php

// Выбрать ингредиенты → получить рецепты и блюда
$constructor = resolve(FoodAIConstructorService::class);

$result = $constructor->suggestRecipesAndDishes(
    ingredients: ['tomato', 'pasta', 'basil'],
    userId: auth()->id(),
    tenantId: tenant()->id,
    constraints: ['vegetarian', 'low_calorie'], // из profile
);

// Возвращает: рецепты, блюда ресторанов, готовые наборы
```

---

## 11. Performance & Optimization

```
Redis кэширование (TTL 24h):
  - taste:explicit:{tenantId}:{userId}
  - taste:implicit:{tenantId}:{userId}
  - taste:profile:{tenantId}:{userId}
  - taste:recommendations:{tenantId}:{userId}:{vertical}

Database индексы:
  - (tenant_id, user_id) → UNIQUE
  - last_calculated_at → для сортировки в Job
  - opt_out → для фильтрации

Query optimization:
  - Embedding calculations лучше в Redis/кэше, чем в БД
  - Batch processing в Jobs (макс 1000 профилей за раз)
  - Cosine similarity в памяти PHP, не в SQL
```

---

## 12. Troubleshooting

### Проблема: CTR низкий (< 5%)

**Решение:**
1. Проверить корректность embeddings (similarity > 0.5 ?)
2. Проверить качество interaction_history (достаточно ли взаимодействий ?)
3. Снизить вес ML с 40% до 25% в гибридных рекомендациях
4. Добавить больше разнообразия (увеличить процент новинок)

### Проблема: Embedding'и не обновляются

**Решение:**
1. Проверить, запущен ли MLRecalculateUserTastesJob (в очереди)
2. Проверить logs в `log.channel('audit')`
3. Проверить interaction_count > 0 в профиле
4. Вручную вызвать `MLRecalculateUserTastesJob::dispatch()` для debugging

### Проблема: Памяти не хватает на обновление 1000 профилей

**Решение:**
1. Уменьшить batch size с 1000 на 500 в Job
2. Использовать `--memory=512M` при запуске queue:work
3. Запустить Job в несколько потоков (используя supervisor)

---

## 13. Дальнейшие расширения (Roadmap)

```
Q2 2026:
  ✓ Интеграция pgvector для PostgreSQL
  ✓ OpenAI embeddings вместо SentenceTransformers
  ✓ A/B тестирование моделей рекомендаций

Q3 2026:
  ✓ Collaborative filtering (рекомендации на основе похожих пользователей)
  ✓ Real-time embeddings recalculation (вместо ежедневного)
  ✓ AR try-on integration для Beauty & Fashion

Q4 2026:
  ✓ Deep Learning модели (LSTM для временных рядов)
  ✓ Multi-language support для embeddings
  ✓ Federated Learning для конфиденциальности пользователей
```

---

**Production Ready Status: ✅ READY**

Все файлы созданы с соответствием CANON 2026, включая UTF-8 кодировку, CRLF окончания, declare(strict_types=1), correlation_id логирование, DB::transaction() и аудит.
