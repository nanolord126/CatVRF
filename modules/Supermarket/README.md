# Supermarket Vertical

Продуктовый маркетплейс с AI-диагностикой, рекомендациями, динамическим ценообразованием, подписками и интеграцией с Честным ЗНАК.

## Структура модуля

```
modules/Supermarket/
├── Application/
│   ├── DTOs/                 # Data Transfer Objects
│   ├── Services/             # Бизнес-логика
│   └── UseCases/             # Use Cases
├── Domain/
│   ├── Entities/             # Доменные сущности
│   ├── Enums/                # Перечисления
│   ├── Exceptions/           # Исключения
│   ├── Repositories/         # Интерфейсы репозиториев
│   └── ValueObjects/         # Value Objects
├── Infrastructure/
│   ├── Models/               # Eloquent модели
│   ├── Repositories/         # Реализации репозиториев
│   └── Providers/            # Service Providers
├── Presentation/
│   └── Http/
│       ├── Controllers/      # API контроллеры
│       ├── Requests/         # Form Requests
│       └── Routes/           # API routes
├── Filament/
│   ├── Resources/            # Filament ресурсы
│   └── Pages/                # Filament страницы
└── Database/
    ├── Migrations/           # Миграции БД
    └── Factories/            # Фабрики для тестов
```

## Основные возможности

### 1. Система возвратов

- **ReturnService** - управление возвратами товаров
- **ReturnPolicyService** - проверка правил возвратов по SubVertical
- Поддержка холодной цепи с особыми правилами
- Автоодобрение возвратов для повреждённых товаров холодной цепи
- Модели: `Return`, `ReturnItem`, `ReturnPolicy`

### 2. Честный ЗНАК (Markirovka)

- **HonestyMarkService** - интеграция с системой маркировки
- Валидация Data Matrix кодов
- Списание кодов маркировки при продаже
- Поддержка Veterinary сертификатов
- Модели: `ProductMark`, `Certificate`

### 3. Возрастная верификация 18+

- **AgeVerificationService** - проверка возраста покупателей
- Верификация через паспорт, selfie, BankID, Госуслуги
- Кэширование результатов верификации
- Блокировка товаров 18+ без верификации

### 4. Подписки (Subscriptions)

- **SubscriptionService** - управление подписками на товары
- Еженедельные, раз в 2 недели, ежемесячные подписки
- Автоматическое создание заказов по расписанию
- Пауза/возобновление/отмена подписок
- Модели: `Subscription`, `SubscriptionItem`, `SubscriptionPayment`

### 5. Уведомления подписок

- **SubscriptionNotificationService** - отправка уведомлений
- Напоминания о доставке (настраиваемое время)
- Уведомления о проблемах с оплатой
- Поддержка Telegram, WhatsApp, Push, Database

### 6. A/B тестирование уведомлений

- Интеграция с общим сервисом A/B тестирования
- Варианты A/B для разных типов уведомлений
- Логирование показов и конверсий

### 7. Аналитика продавца

- **SellerAnalyticsService** - полная аналитика для продавцов
- Выручка, заказы, средний чек, конверсия
- Разбивка по SubVertical, топ-товары
- B2B vs B2C статистика
- Возвраты и отмены
- Тренды и сравнение с предыдущим периодом

### 8. AI Insights

- **AIInsightGenerator** - генерация AI-инсайтов для продавцов
- Интеграция с OpenAI GPT-4o-mini
- Actionable рекомендации по росту выручки
- Автоматическое обновление каждые 36 часов
- Модель: `SellerInsight`

### 9. Платежи подписок

- **SubscriptionPaymentService** - обработка рекуррентных платежей
- B2C: рекуррентные списания с карты
- B2B: выставление счётов через Точка Банк
- Retry логика (3 попытки с экспоненциальной задержкой)
- Автоматическая пауза подписки при неуспехе

### 10. Отчёты по платежам

- **SellerPaymentReportService** - финансовые отчёты
- KPI: выручка, полученные средства, комиссии
- Тренды выручки по дням
- Разбивка по методам оплаты
- Статистика по подпискам
- Отчёты по возвратам
- B2B статистика

## Миграции базы данных

### Созданные таблицы:
- `supermarket_returns` - возвраты
- `supermarket_return_items` - товары в возврате
- `supermarket_return_policies` - политики возвратов
- `supermarket_product_marks` - коды маркировки
- `supermarket_certificates` - сертификаты
- `supermarket_subscriptions` - подписки
- `supermarket_subscription_items` - товары в подписке
- `supermarket_subscription_payments` - платежи подписок
- `supermarket_seller_insights` - AI инсайты

## Конфигурация

### config/supermarket.php

```php
return [
    'vertical_name' => 'Supermarket',
    'enabled' => true,
    
    'commissions' => [
        'rate' => 0.05,
        'min_amount' => 10,
    ],
    
    'returns' => [
        'default_policy' => [
            'max_days' => 3,
            'auto_approve_hours' => 12,
        ],
    ],
    
    'subscriptions' => [
        'min_amount' => 1500,
        'min_items' => 8,
    ],
    
    'age_verification' => [
        'enabled' => true,
        'required_age' => 18,
    ],
];
```

### Переменные окружения

```env
SUPERMARKET_ENABLED=true
SUPERMARKET_COMMISSION_RATE=0.05
SUPERMARKET_DEFAULT_RETURN_DAYS=3
SUPERMARKET_SUBSCRIPTION_MIN_AMOUNT=1500
SUPERMARKET_AGE_VERIFICATION_ENABLED=true
SUPERMARKET_HONESTY_MARK_ENABLED=true
```

## Filament ресурсы

### Ресурсы:
- **ReturnResource** - управление возвратами
  - Actions: Approve, Reject, Complete
  - Фильтры по статусу, причине, холодной цепи
  
- **ReturnPolicyResource** - настройка политик возвратов
  - Правила по SubVertical
  - Настройка причин возвратов
  - Требования к фото/температуре

## Команды

### Обработка подписок
```bash
php artisan subscriptions:process
```
Обрабатывает подписки, до которых подошло время доставки, и создаёт заказы.

### Напоминания о доставке
```bash
php artisan subscriptions:reminders --hours=24
```
Отправляет напоминания о доставке за N часов.

## Очереди

Используются следующие очереди:
- `supermarket` - стандартные задачи
- `supermarket-high` - приоритетные (платежи)
- `supermarket-low` - низкий приоритет

## Интеграции

### Внешние сервисы:
- **Честный ЗНАК** - маркировка товаров
- **OpenAI GPT-4o-mini** - генерация инсайтов
- **Точка Банк** - B2B платежи
- **Тинькофф** - B2C платежи
- **Telegram/WhatsApp** - уведомления

### Внутренние сервисы:
- **PaymentGatewayFactory** - фабрика шлюзов
- **InternalCRMAdapter** - синхронизация с CRM
- **ABTestService** - A/B тестирование уведомлений

## Требования к файлам

Все файлы должны быть минимум 60 строк (включая фасады).

## Архитектурные принципы

- **Clean Architecture** - чёткое разделение слоёв
- **DDD** - Domain-Driven Design
- **SOLID** - принципы SOLID
- **DTO** - immutable, readonly, strict typing
- **Final классы** - везде где возможно
- **PHP 8.3+** - strict_types=1
- **Laravel 11+** - актуальная версия

## Мониторинг

- Логирование всех ключевых операций
- Audit лог для финансовых операций
- Метрики через OpenTelemetry
- Alertmanager для критических ошибок

## Тестирование

- Unit тесты для сервисов
- Feature тесты для API endpoints
- Contract тесты для интеграций
- Фабрики для всех моделей

## Документация

Полная спецификация доступна в `.github/supermarket.md`

## Лицензия

CatVRF Project License
