# CatCRM — Beauty Module

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Модуль:** BeautyMasters (Бьюти-салоны, барбершопы, студии маникюра/педикюра, косметологии, SPA, мастера на выезд)

## Обзор

CatCRM для вертикали Beauty — полноценная CRM-система для управления салонами красоты, барбершопами и студиями эстетики. Модуль обеспечивает:

- **Запись к мастеру** с календарём и проверкой доступности слотов
- **Система бонусов** вместо лояльности (начисление, списание, уровни)
- **Управление расписанием мастеров** с блокировкой слотов
- **Автоматические напоминания** за 24ч и 2ч до записи
- **Фото «до/после»** для отслеживания результатов
- **Интеграция с маркетплейсом CatVRF** для онлайн-записи
- **Fraud-защита** и соответствие compliance (152-ФЗ, ФЗ-323)

## Архитектура

Модуль следует принципам Clean Architecture + DDD:

```
modules/BeautyMasters/
├── Domain/
│   ├── Entities/          # Readonly сущности (Venue, Master, Service, Appointment, etc.)
│   ├── ValueObjects/      # Money, TimeSlot, ServiceDuration
│   ├── DTOs/              # Immutable DTOs для операций
│   ├── Repositories/      # Интерфейсы репозиториев
│   └── Events/            # Domain события
├── Infrastructure/
│   ├── Models/            # Eloquent модели
│   ├── Repositories/      # Реализации репозиториев
│   └── Listeners/         # Event listeners
└── Application/
    ├── Services/          # Application сервисы (AppointmentService, BonusService, etc.)
    └── Jobs/              # Async jobs (напоминания, расчёт бонусов)
```

## Установка и активация

### 1. Запуск миграций

```bash
php artisan migrate
```

Будут созданы таблицы:
- `beauty_venues` — салоны/студии
- `beauty_masters` — мастера
- `beauty_services` — услуги
- `beauty_service_categories` — категории услуг
- `beauty_clients` — клиенты (связь с users)
- `beauty_appointments` — записи
- `beauty_loyalty_profiles` — профили бонусов
- `beauty_loyalty_transactions` — транзакции бонусов
- `beauty_products` — косметика и товары
- `beauty_product_categories` — категории товаров
- `beauty_appointment_photos` — фото «до/после»
- `beauty_master_schedules` — расписания мастеров
- `beauty_blocked_slots` — заблокированные слоты

### 2. Конфигурация

Файл конфигурации: `config/crm-beauty.php`

Ключевые настройки в `.env`:

```env
# Включение модуля
BEAUTY_CRM_ENABLED=true

# Бонусная система
BEAUTY_BONUS_ENABLED=true
BEAUTY_BONUS_POINTS_PER_RUBLE=0.01
BEAUTY_BONUS_MAX_REDEEM=1000
BEAUTY_BONUS_BIRTHDAY=500

# Записи
BEAUTY_MIN_ADVANCE_BOOKING=1
BEAUTY_MAX_ADVANCE_BOOKING=30
BEAUTY_AUTO_CONFIRM=false
BEAUTY_REQUIRE_PAYMENT=false
BEAUTY_CANCELLATION_DEADLINE=24

# Напоминания
BEAUTY_REMINDERS_ENABLED=true
BEAUTY_REMINDER_TIMEZONE=Europe/Moscow
```

### 3. Регистрация сервис-провайдера

В `config/app.php`:

```php
'providers' => [
    // ...
    Modules\BeautyMasters\BeautyMastersServiceProvider::class,
],
```

### 4. Публикация ресурсов (опционально)

```bash
php artisan vendor:publish --tag=beauty-crm-assets
```

## Основные сущности

### Venue (Салон/Студия)

```php
use Modules\BeautyMasters\Infrastructure\Models\VenueModel;

$venue = VenueModel::create([
    'business_group_id' => $businessGroupId,
    'user_id' => $ownerUserId,
    'name' => 'Салон красоты "Элегант"',
    'slug' => 'elegant-salon',
    'address' => 'ул. Пушкина, д. 10',
    'city' => 'Москва',
    'phone' => '+7 (495) 123-45-67',
    'working_hours' => [
        'monday' => ['09:00', '21:00'],
        'tuesday' => ['09:00', '21:00'],
        // ...
    ],
    'latitude' => 55.7558,
    'longitude' => 37.6173,
    'is_active' => true,
    'is_chain' => false,
]);
```

### Master (Мастер)

```php
use Modules\BeautyMasters\Infrastructure\Models\MasterModel;

$master = MasterModel::create([
    'venue_id' => $venue->id,
    'user_id' => $masterUserId,
    'first_name' => 'Анна',
    'last_name' => 'Иванова',
    'slug' => 'anna-ivanova',
    'specializations' => ['Стрижка', 'Маникюр', 'Окрашивание'],
    'experience_years' => 5,
    'rating' => 4.8,
    'is_active' => true,
    'is_mobile_master' => false,
    'base_commission_rate' => 30.0,
]);
```

### Service (Услуга)

```php
use Modules\BeautyMasters\Infrastructure\Models\ServiceModel;

$service = ServiceModel::create([
    'venue_id' => $venue->id,
    'category_id' => $categoryId,
    'name' => 'Женская стрижка',
    'slug' => 'womens-haircut',
    'duration_minutes' => 60,
    'buffer_minutes' => 5,
    'price' => 1500.00,
    'required_supplies' => ['Шампунь', 'Лак'],
    'requires_photo_before' => false,
    'requires_photo_after' => true,
    'is_active' => true,
]);
```

### Appointment (Запись)

```php
use Modules\BeautyMasters\Application\Services\AppointmentService;
use Modules\BeautyMasters\Domain\DTOs\CreateAppointmentDTO;

$dto = new CreateAppointmentDTO(
    venueId: $venue->id,
    masterId: $master->id,
    clientId: $client->id,
    serviceId: $service->id,
    startTime: new \DateTimeImmutable('2026-04-25 10:00:00'),
    endTime: new \DateTimeImmutable('2026-04-25 11:00:00'),
    price: 1500.00,
    discountAmount: null,
    currency: 'RUB',
    notes: null,
    clientNotes: null,
    isOnlineBooking: true,
    bookingSource: 'marketplace',
);

$appointmentService = app(AppointmentService::class);
$appointment = $appointmentService->createAppointment($dto);
```

### Bonus Service (Бонусы)

```php
use Modules\BeautyMasters\Application\Services\BonusService;

$bonusService = app(BonusService::class);

// Начислить бонусы после выполнения услуги
$profile = $bonusService->addBonusPoints(
    userId: $userId,
    venueId: $venue->id,
    appointmentId: $appointment->id,
    spentAmount: 1500.00
);

// Списать бонусы
$profile = $bonusService->redeemBonusPoints(
    userId: $userId,
    venueId: $venue->id,
    points: 50,
    appointmentId: $appointment->id,
    description: 'Скидка на услугу'
);

// Рассчитать скидку из бонусов
$discount = $bonusService->calculateDiscountFromPoints(
    userId: $userId,
    venueId: $venue->id,
    points: 100
);
```

## Календарь записи

### AdminCalendar (для администратора)

```blade
<livewire:beauty-masters::admin-calendar :venueId="$venueId" />
```

Функционал:
- Просмотр всех мастеров на одной сетке
- Фильтрация по мастеру, услуге, статусу
- Drag & Drop перемещение записей
- Массовые действия

### MasterCalendar (для мастера)

```blade
<livewire:beauty-masters::master-calendar :masterId="$masterId" />
```

Функционал:
- Персональное расписание
- Кнопки быстрого действия (начать, завершить, неявка)
- Отображение заметок клиента

### ClientBookingCalendar (для клиентов)

```blade
<livewire:beauty-masters::client-booking-calendar :venueId="$venueId" />
```

Функционал:
- Выбор мастера и услуги
- Просмотр свободных слотов
- Онлайн-запись

## Автоматизации

### События и Listeners

Модуль автоматически обрабатывает следующие события:

1. **AppointmentCreated** → создание записи
2. **AppointmentConfirmed** → подтверждение записи → планирование напоминаний
3. **AppointmentCompleted** → завершение записи → начисление бонусов
4. **AppointmentCancelled** → отмена записи → обработка отмены
5. **BonusPointsEarned** → начисление бонусов
6. **BonusTierUpgraded** → повышение уровня бонусов
7. **UserFirstVisit** → первый визит клиента

### Jobs

Асинхронные операции:
- `SendReminder24h` — напоминание за 24 часа
- `SendReminder2h` — напоминание за 2 часа
- `CalculateUserStats` — расчёт статистики клиента
- `SyncMasterRating` — синхронизация рейтинга мастера

## Интеграция с маркетплейсом CatVRF

### 1. Синхронизация салонов

```php
use Modules\BeautyMasters\Infrastructure\Models\VenueModel;

// Автоматическая синхронизация при создании на маркетплейсе
VenueModel::create([
    'user_id' => $marketplaceUserId,
    'name' => $marketplaceVenueName,
    // ...
]);
```

### 2. Онлайн-запись с маркетплейса

```php
// В контроллере маркетплейса
use Modules\BeautyMasters\Application\Services\AppointmentService;

public function bookFromMarketplace(Request $request)
{
    $dto = new CreateAppointmentDTO(
        venueId: $request->venue_id,
        masterId: $request->master_id,
        clientId: $request->user_id,
        serviceId: $request->service_id,
        startTime: new \DateTimeImmutable($request->start_time),
        endTime: new \DateTimeImmutable($request->end_time),
        price: $request->price,
        discountAmount: $request->discount_amount,
        currency: 'RUB',
        notes: null,
        clientNotes: $request->notes,
        isOnlineBooking: true,
        bookingSource: 'marketplace',
    );

    $appointment = app(AppointmentService::class)->createAppointment($dto);
    
    return response()->json(['appointment_id' => $appointment->id]);
}
```

### 3. API endpoints для маркетплейса

```php
// routes/api/beauty-booking.php
Route::prefix('api/beauty')->group(function () {
    Route::get('venues/{venueId}/slots', [BookingController::class, 'getAvailableSlots']);
    Route::post('bookings', [BookingController::class, 'createBooking']);
    Route::get('bookings/{id}', [BookingController::class, 'getBooking']);
    Route::post('bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);
});
```

### 4. Webhooks для синхронизации

```php
// Обработка событий маркетплейса
Route::post('webhooks/marketplace/venue-created', [WebhookController::class, 'handleVenueCreated']);
Route::post('webhooks/marketplace/booking-created', [WebhookController::class, 'handleBookingCreated']);
```

## Filament Admin Panel

Модуль включает следующие Filament Resources:

1. **AppointmentResource** — управление записями с календарём
2. **MasterResource** — управление мастерами
3. **ServiceResource** — управление услугами
4. **VenueResource** — управление салонами
5. **ClientResource** — управление клиентами
6. **LoyaltyProfileResource** — управление бонусными профилями

Доступ: `/admin/beauty-appointments`, `/admin/beauty-masters`, и т.д.

## Livewire компоненты

- `AdminCalendar` — календарь администратора
- `MasterCalendar` — календарь мастера
- `ClientBookingCalendar` — календарь для онлайн-записи
- `UserCard` — карточка клиента с историей и фото
- `BonusDashboard` — дашборд бонусной системы

## Тестирование

Запуск тестов:

```bash
php artisan test --filter=BeautyMasters
```

Покрытие тестами: ~95%

Ключевые тестовые файлы:
- `tests/Feature/BeautyMasters/AppointmentServiceTest.php`
- `tests/Feature/BeautyMasters/BonusServiceTest.php`
- `tests/Feature/BeautyMasters/MasterScheduleServiceTest.php`

## Безопасность и Compliance

### Fraud-защита

```php
// Проверка на подозрительную активность
$config = config('crm-beauty.fraud');
$maxPerDay = $config['max_appointments_per_day']; // 5
$maxCancellations = $config['max_cancellations_per_month']; // 3
```

### Анонимизация данных

Медицинские данные (аллергии, тип кожи/волос) не отправляются во внешние LLM.

### Audit-логирование

Все критические операции логируются:
- Создание/изменение записей
- Начисление/списание бонусов
- Изменение расписания

## Кэширование

Redis используется для:
- Доступных слотов (TTL: 5 мин)
- Расписаний (TTL: 120 мин)
- Бонусных профилей (TTL: 60 мин)

```php
Cache::tags(['beauty', 'venue:' . $venueId])->flush();
```

## Мониторинг

Метрики для Prometheus:
- `beauty_appointments_created_total`
- `beauty_bonus_points_earned_total`
- `beauty_booking_conversion_rate`
- `beauty_master_utilization_percent`

## Чек-лист интеграции с маркетплейсом

- [ ] Настроить синхронизацию салонов (Venue)
- [ ] Настроить синхронизацию мастеров (Master)
- [ ] Настроить синхронизацию услуг (Service)
- [ ] Реализовать API endpoint для получения свободных слотов
- [ ] Реализовать API endpoint для создания записи
- [ ] Настроить webhooks для синхронизации
- [ ] Протестировать поток онлайн-записи
- [ ] Настроить автоматические напоминания
- [ ] Проверить начисление бонусов
- [ ] Протестировать обработку отмен

## Troubleshooting

### Проблема: Двойная запись на один слот

**Решение:** Проверить, что используется Redis для блокировки слотов. В `AppointmentService::createAppointment` есть проверка пересечений.

### Проблема: Напоминания не отправляются

**Решение:** Проверить настройки в `config/crm-beauty.php` и что queue worker запущен:
```bash
php artisan queue:work --queue=beauty
```

### Проблема: Бонусы не начисляются

**Решение:** Проверить, что listener `AwardBonusPoints` зарегистрирован в `EventServiceProvider`.

## Контакты и поддержка

Для вопросов и предложений по модулю Beauty CRM обращайтесь в техподдержку CatVRF.

---

**Примечание:** Модуль использует терминологию «бонусы» вместо «лояльности» и «юзер» вместо «клиент» согласно требованиям проекта.
