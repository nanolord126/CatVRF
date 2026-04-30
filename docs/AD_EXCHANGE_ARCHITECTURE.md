# CatVRF Ad Exchange Architecture

**Версия:** 1.0  
**Дата:** 28.04.2026  
**Вертикаль:** Advertising Modern Exchange

## Обзор

Современная программная рекламная биржа с поддержкой:
- **Shorts** - короткие видео/рекламные ролики (TikTok/Reels формат)
- **Аукционы** - bidding для рекламных слотов
- **RTB** - Real-Time Bidding для мгновенной покупки инвентаря
- **Programmatic** - автоматическая покупка/продажа рекламы
- **Publisher Integration** - интеграция с паблишерами

## Архитектура

```
┌─────────────────────────────────────────────────────────────────┐
│                      Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐        │
│  │  Shorts API  │  │ Auction API  │  │   RTB API    │        │
│  └──────────────┘  └──────────────┘  └──────────────┘        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Use Cases Layer                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐        │
│  │CreateShort   │  │PlaceBid      │  │RTBRequest    │        │
│  │ModerateShort │  │CloseAuction  │  │BidResponse   │        │
│  │ScheduleShort │  │WinningBid    │  │AuctionEngine │        │
│  └──────────────┘  └──────────────┘  └──────────────┘        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                        Domain Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐        │
│  │  AdShort     │  │   Auction    │  │   Bid        │        │
│  │  AdInventory │  │  BidHistory  │  │  Publisher   │        │
│  └──────────────┘  └──────────────┘  └──────────────┘        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐        │
│  │  Repository  │  │  Queue       │  │  Cache       │        │
│  │  Events      │  │  WebSocket   │  │  ClickHouse  │        │
│  └──────────────┘  └──────────────┘  └──────────────┘        │
└─────────────────────────────────────────────────────────────────┘
```

## Доменные Сущности

### 1. AdShort (Рекламный шорт)

```php
readonly class AdShort {
    public int $id;
    public string $uuid;
    public int $tenant_id;
    public string $title;
    public string $video_url;
    public string $thumbnail_url;
    public int $duration_seconds; // 15-60 сек
    public string $status; // draft, pending_review, active, paused, rejected
    public Carbon $start_at;
    public Carbon $end_at;
    public int $budget;
    public int $spent;
    public string $pricing_model; // cpm, cpc, cpa, cpv
    public array $targeting_criteria;
    public string $correlation_id;
}
```

### 2. Auction (Аукцион)

```php
readonly class Auction {
    public int $id;
    public string $uuid;
    public int $tenant_id;
    public string $name;
    public string $type; // forward, dutch, sealed_bid
    public string $status; // upcoming, active, closed, cancelled
    public Carbon $start_at;
    public Carbon $end_at;
    public int $starting_price;
    public int $current_price;
    public int $reserve_price;
    public int $inventory_id;
    public array $bid_history;
    public string $correlation_id;
}
```

### 3. Bid (Ставка)

```php
readonly class Bid {
    public int $id;
    public string $uuid;
    public int $auction_id;
    public int $tenant_id;
    public int $bidder_id;
    public int $amount;
    public string $status; // pending, winning, losing, withdrawn
    public Carbon $placed_at;
    public string $correlation_id;
}
```

### 4. AdInventory (Инвентарь)

```php
readonly class AdInventory {
    public int $id;
    public string $uuid;
    public int $publisher_id;
    public string $inventory_type; // short, banner, video, native
    public string $placement; // feed, story, search, etc.
    public int $available_impressions;
    public int $reserved_impressions;
    public Carbon $available_from;
    public Carbon $available_until;
    public string $status; // available, reserved, sold_out
    public array $targeting_restrictions;
}
```

## Use Cases

### Shorts Use Cases
- `CreateShortUseCase` - Создание рекламного шорта
- `ModerateShortUseCase` - Модерация контента
- `ScheduleShortUseCase` - Планирование показов
- `TrackShortMetricsUseCase` - Отслеживание метрик (views, likes, shares)

### Auction Use Cases
- `CreateAuctionUseCase` - Создание аукциона
- `PlaceBidUseCase` - Размещение ставки
- `CloseAuctionUseCase` - Закрытие аукциона
- `DetermineWinnerUseCase` - Определение победителя
- `AutoBidUseCase` - Автоматический bidding

### RTB Use Cases
- `ProcessRTBRequestUseCase` - Обработка RTB запроса
- `GenerateBidResponseUseCase` - Генерация bid response
- `WinNotificationUseCase` - Уведомление о победе
- `ImpressionTrackingUseCase` - Отслеживание показа

## Pricing Models

### Shorts-Specific
- **CPV** (Cost Per View) - оплата за просмотр шорта
- **CPE** (Cost Per Engagement) - оплата за взаимодействие (like, share, comment)
- **CPM** (Cost Per 1000 Impressions) - классическая модель
- **CPC** (Cost Per Click) - оплата за клик

### Auction-Specific
- **First-Price Auction** - победитель платит свою ставку
- **Second-Price Auction** (Vickrey) - победитель платит вторую по величине ставку
- **Dutch Auction** - цена снижается до первой ставки

## Технические Требования

### Performance
- RTB запросы обрабатываются < 100ms
- Поддержка 50k+ RPS для аукционов
- Redis для real-time bidding
- ClickHouse для аналитики

### Безопасность
- Fraud detection для всех ставок
- Rate limiting для API
- Audit logging всех транзакций
- PII anonymization compliance (152-ФЗ)

### Интеграции
- Publisher API для паблишеров
- SSP (Supply-Side Platform) интеграция
- DSP (Demand-Side Platform) интеграция
- Video transcoding service

## API Эндпоинты

### Shorts
```
POST /api/ad-shorts - Создание шорта
GET /api/ad-shorts/{id} - Получение шорта
PUT /api/ad-shorts/{id} - Обновление шорта
POST /api/ad-shorts/{id}/approve - Одобрение шорта
POST /api/ad-shorts/{id}/reject - Отклонение шорта
GET /api/ad-shorts/{id}/metrics - Метрики шорта
```

### Auctions
```
POST /api/auctions - Создание аукциона
GET /api/auctions/{id} - Получение аукциона
POST /api/auctions/{id}/bid - Размещение ставки
POST /api/auctions/{id}/close - Закрытие аукциона
GET /api/auctions/{id}/bids - История ставок
```

### RTB
```
POST /api/rtb/bid - RTB bid request
POST /api/rtb/win - Win notification
POST /api/rtb/impression - Impression tracking
POST /api/rtb/click - Click tracking
```

## Filament Admin

### AdShortResource
- Загрузка видео через S3
- Модерация контента
- Планирование показов
- Просмотр метрик
- Управление бюджетом

### AuctionResource
- Создание аукционов
- Мониторинг ставок в реальном времени
- Закрытие аукционов
- История транзакций

### PublisherResource
- Управление паблишерами
- Инвентарь
- Revenue share настройки
