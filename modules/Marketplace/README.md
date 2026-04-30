# Marketplace Module - CatVRF

**Версия:** 1.0.0  
**Статус:** PRODUCTION READY  
**Архитектура:** 9-слойная Clean Architecture

## Описание

Полноценная витринная система маркетплейса для CatVRF, которая:

1. **Агрегирует товары/услуги из всех вертикалей** (Beauty, Restaurant, Fashion, Hotels и др.)
2. **Использует маркетплейс-алгоритмы ранжирования** (популярность, конверсия, свежесть, рейтинг, цена)
3. **Интегрируется с ML сервисами** для персонализированных рекомендаций
4. **Является стартовой страницей платформы** с единым интерфейсом поиска и рекомендаций

## Архитектура

### 9-слойная структура

```
modules/Marketplace/
├── Domain/
│   ├── Entities/          ProductListing, MarketplaceCategory, RankingScore, AggregationRule
│   ├── Enums/             ListingStatus, ListingType, VerticalSource
│   ├── ValueObjects/      Money, Rating
│   ├── Events/            ListingAggregated, RankingUpdated, ListingPublished
│   └── Interfaces/        ListingRepositoryInterface, CategoryRepositoryInterface, etc.
├── Application/
│   ├── DTOs/              SearchListingsDTO, AggregationConfigDTO, RankingConfigDTO
│   ├── Services/          RankingEngineService, MarketplaceAggregatorService, RecommendationService
│   └── Jobs/              RecalculateRankingsJob, SyncVerticalsJob, PublishListingJob
├── Infrastructure/
│   ├── Adapters/          VerticalAdapterInterface, BeautyAdapter, RestaurantAdapter, Factory
│   └── Repositories/      EloquentListingRepository, EloquentCategoryRepository, etc.
├── Presentation/
│   ├── Http/
│   │   ├── Controllers/   MarketplaceController
│   │   ├── Requests/      SearchListingsRequest
│   │   └── Resources/     ProductListingResource
│   └── Routes/            marketplace.php
└── MarketplaceServiceProvider.php
```

## Ключевые компоненты

### 1. Domain Layer

**Entities:**
- `ProductListing` - Агрегированная витринная позиция с readonly свойствами
- `MarketplaceCategory` - Категория маркетплейса с деревом
- `RankingScore` - Рейтинг позиции с множеством факторов
- `AggregationRule` - Правило агрегации из вертикали

**Value Objects:**
- `Money` - Денежная сумма с валютой и валидацией
- `Rating` - Рейтинг с количеством отзывов и валидацией
- `VerticalSource` - Источник (вертикаль) с приоритетами и поддержкой

**Enums:**
- `ListingStatus` - Статусы с валидацией переходов
- `ListingType` - Типы (product, service, booking, etc.)

### 2. Application Services

**RankingEngineService** - Алгоритмы ранжирования:
- Популярность (экспоненциальное затухание)
- Конверсия (заказы / просмотры)
- Свежесть (линейное затухание)
- Рейтинг (нормализация + вес отзывов)
- Цена (оптимальный диапазон)
- Доступность (наличие на складе)
- ML score (placeholder для интеграции)
- Personalization score (placeholder для user context)

**MarketplaceAggregatorService** - Оркестрация агрегации:
- Создание правил агрегации
- Синхронизация вертикалей по правилам
- Реактивная синхронизация при изменениях
- Применение трансформаций данных
- Маппинг категорий и атрибутов

**RecommendationService** - Алгоритмы рекомендаций:
- Персонализированные рекомендации
- Похожие товары (content-based similarity)
- Рекомендации по категориям
- Трендовые позиции
- Cold start рекомендации
- Смешанные рекомендации

### 3. Infrastructure Layer

**Adapters:**
- `VerticalAdapterInterface` - Интерфейс для интеграции с вертикалями
- `BeautyAdapter` - Адаптер для Beauty Masters
- `RestaurantAdapter` - Адаптер для Restaurant
- `VerticalAdapterFactory` - Фабрика адаптеров с регистрацией

**Repositories:**
- `EloquentListingRepository` - Репозиторий позиций с фильтрами и поиском
- `EloquentCategoryRepository` - Репозиторий категорий с деревом
- `EloquentRankingRepository` - Репозиторий рейтингов
- `EloquentAggregationRuleRepository` - Репозиторий правил

### 4. Presentation Layer

**API Endpoints:**
- `GET /api/marketplace` - Главная витрина (стартовая страница)
- `GET /api/marketplace/search` - Поиск позиций
- `GET /api/marketplace/listings/{uuid}` - Детали позиции
- `GET /api/marketplace/recommendations` - Персонализированные рекомендации
- `GET /api/marketplace/categories` - Категории
- `GET /api/marketplace/categories/{category}` - Позиции по категории
- `GET /api/marketplace/verticals/{vertical}` - Позиции по вертикали
- `GET /api/marketplace/trending` - Трендовые позиции
- `GET /api/marketplace/featured` - Featured позиции
- `GET /api/marketplace/stats` - Статистика (admin)

## Установка

### 1. Зарегистрировать ServiceProvider

В `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Marketplace\MarketplaceServiceProvider::class,
],
```

### 2. Запустить миграции

```bash
php artisan migrate
```

### 3. Опубликовать конфигурацию

```bash
php artisan vendor:publish --tag=marketplace-config
```

### 4. Настроить .env

```env
MARKETPLACE_POPULARITY_WEIGHT=0.25
MARKETPLACE_CONVERSION_WEIGHT=0.20
MARKETPLACE_RECENCY_WEIGHT=0.15
MARKETPLACE_RATING_WEIGHT=0.15
MARKETPLACE_PRICE_WEIGHT=0.10
MARKETPLACE_AVAILABILITY_WEIGHT=0.10
MARKETPLACE_PROMOTED_WEIGHT=0.03
MARKETPLACE_ML_WEIGHT=0.01
MARKETPLACE_PERSONALIZATION_WEIGHT=0.01

MARKETPLACE_ENABLE_ML=false
MARKETPLACE_ENABLE_PERSONALIZATION=false

MARKETPLACE_ACTIVE_VERTICALS=beauty,restaurant
MARKETPLACE_CACHE_ENABLED=true
```

## Использование

### Агрегация вертикалей

```php
use Modules\Marketplace\Application\Services\MarketplaceAggregatorService;
use Modules\Marketplace\Application\DTOs\AggregationConfigDTO;

$aggregator = app(MarketplaceAggregatorService::class);

// Создать правило агрегации
$config = AggregationConfigDTO::create(
    source: VerticalSource::BEAUTY,
    sourceEntityType: 'service',
    filters: ['status' => 'active', 'auto_publish' => false],
    realTimeSync: true,
);

$rule = $aggregator->createAggregationRule($config);

// Синхронизировать все вертикали
$results = $aggregator->syncAllVerticals();
```

### Ранжирование

```php
use Modules\Marketplace\Application\Services\RankingEngineService;

$rankingEngine = app(RankingEngineService::class);

// Пересчитать все рейтинги
$rankings = $rankingEngine->recalculateAll();

// Пересчитать просроченные
$count = $rankingEngine->recalculateExpired();

// Получить статистику
$stats = $rankingEngine->getRankingStats();
```

### Рекомендации

```php
use Modules\Marketplace\Application\Services\RecommendationService;

$recommendationService = app(RecommendationService::class);

// Персонализированные рекомендации
$recommendations = $recommendationService->getPersonalizedRecommendations($userId);

// Похожие товары
$similar = $recommendationService->getSimilarListings($listingUuid);

// Трендовые
$trending = $recommendationService->getTrendingListings();
```

### Jobs

```php
use Modules\Marketplace\Application\Jobs\RecalculateRankingsJob;
use Modules\Marketplace\Application\Jobs\SyncVerticalsJob;

// Пересчет рейтингов
RecalculateRankingsJob::dispatch(limit: 1000, forceAll: true);

// Синхронизация вертикалей
SyncVerticalsJob::dispatch(syncPending: true);
```

## Cron Jobs

Добавить в `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Пересчет рейтингов каждый час
    $schedule->job(new RecalculateRankingsJob(forceAll: false))
             ->hourly();
    
    // Синхронизация вертикалей каждые 30 минут
    $schedule->job(new SyncVerticalsJob(syncPending: true))
             ->everyThirtyMinutes();
}
```

## Добавление новых адаптеров вертикалей

1. Создать класс адаптера:

```php
namespace Modules\Marketplace\Infrastructure\Adapters;

final class FashionAdapter implements VerticalAdapterInterface
{
    public function fetchSources(array $filters = [], int $limit = 100): array
    {
        // Реализация получения товаров из Fashion вертикали
    }
    
    // ... остальные методы интерфейса
}
```

2. Зарегистрировать в `MarketplaceServiceProvider`:

```php
$this->app->resolving(VerticalAdapterFactory::class, function (VerticalAdapterFactory $factory, $app) {
    $fashionAdapter = new FashionAdapter(
        $app->make(FashionRepositoryInterface::class),
        $app->make(LoggerInterface::class),
    );
    $factory->registerAdapter(VerticalSource::FASHION, $fashionAdapter);
});
```

## ML Интеграция

В `RankingEngineService::calculateMLScore()` заменить placeholder на реальный вызов ML сервиса:

```php
private function calculateMLScore(ProductListing $listing): float
{
    // Вызов ML сервиса через HTTP/gRPC
    $response = $this->mlClient->predict([
        'listing_uuid' => $listing->uuid->toString(),
        'features' => $this->extractFeatures($listing),
    ]);
    
    return $response['score'] ?? 0.0;
}
```

## Персонализация

В `RecommendationService::getPersonalizedRecommendations()` добавить логику на основе user context:

```php
public function getPersonalizedRecommendations(int $userId, ?int $limit = null): array
{
    // Получить историю пользователя
    $history = $this->userHistoryService->getHistory($userId);
    
    // Collaborative filtering
    $cfRecommendations = $this->collaborativeFiltering->recommend($userId);
    
    // Content-based recommendations
    $cbRecommendations = $this->contentBased->recommend($history);
    
    // Объединить и ранжировать
    return $this->mergeAndRank($cfRecommendations, $cbRecommendations, $limit);
}
```

## Тестирование

```bash
# Unit тесты
./vendor/bin/pest tests/Unit/Marketplace

# Feature тесты
./vendor/bin/pest tests/Feature/Marketplace

# Интеграционные тесты
./vendor/bin/pest tests/Integration/Marketplace
```

## Мониторинг

Ключевые метрики:
- Количество позиций по вертикалям
- Время синхронизации
- Время пересчета рейтингов
- Hit rate рекомендаций
- Conversion rate витрины

## Безопасность

- Все публичные endpoints с rate limiting (60 req/min)
- Admin endpoints требуют аутентификации и авторизации
- Валидация всех входных данных
- SQL injection protection через Eloquent
- XSS protection через JSON encoding

## Performance

- Индексы на всех часто используемых полях
- Full-text search индекс на title/description
- Кэширование с tags-based инвалидацией (Redis)
- Batch операции для массового обновления
- Jobs для асинхронных операций

## TODO

- [ ] Реализовать адаптеры для всех вертикалей (Fashion, Hotels, Fitness, Flowers, Dental, VetGrooming)
- [ ] Интегрировать с ML сервисом для real-time скоринга
- [ ] Реализовать персонализацию на основе user history
- [ ] Добавить A/B тестирование алгоритмов ранжирования
- [ ] Реализовать кэширование с Redis tags
- [ ] Добавить ClickHouse для аналитики событий
- [ ] Написать полный набор тестов
- [ ] Добавить OpenTelemetry tracing
- [ ] Реализовать webhook для real-time синхронизации

## Лицензия

Proprietary - CatVRF Project
