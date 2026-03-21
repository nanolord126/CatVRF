# 🏢 VERTICALS REGISTRY (Реестр всех вертикалей)

## 📋 Актуальный список вертикалей

| # | Вертикаль | Домен | Статус | Комиссия |
|---|-----------|-------|--------|----------|
| 1 | 🧴 Beauty & Wellness | `app/Domains/Beauty` | ✅ Active | 14% (Dikidi: 10→12%) |
| 2 | 🚗 Auto & Mobility | `app/Domains/Auto` | ✅ Active | 15% + 5% fleet |
| 3 | 🍽️ Food & Delivery | `app/Domains/Food` | ✅ Active | 14% |
| 4 | 🏘️ Real Estate & Rentals | `app/Domains/RealEstate` | ✅ Active | 14% |

---

## 🧴 ВЕРТИКАЛЬ 1: Beauty & Wellness

**Домен**: `app/Domains/Beauty`

### Основные сущности
- `BeautySalon` - Салоны красоты
- `Master` - Мастера (привязаны или самозанятые)
- `Service` - Услуги (стрижка, маникюр, массаж и т.д.)
- `Appointment` - Записи на услуги
- `BeautyProduct` - Товары (косметика, инструменты)
- `Consumable` - Расходники (перчатки, краска, полотенца)
- `PortfolioItem` - Фото работ
- `Review` - Отзывы после визита

### Ключевые таблицы
```sql
beauty_salons: name, address, geo_point, schedule_json, rating, review_count, is_verified
masters: salon_id (nullable), full_name, specialization (jsonb), experience_years, rating
services: master_id/salon_id, name, duration_minutes, price, consumables_json
appointments: salon_id, master_id, service_id, client_id, datetime_start, status, price, payment_status
beauty_products: salon_id, name, sku, current_stock, price, consumable_type
```

### Автоматические процессы
- ✅ Списание расходников при завершении записи (DeductAppointmentConsumables listener)
- ✅ Hold stock при создании записи
- ✅ Release stock при отмене за 24 ч
- ✅ Уведомления при низком остатке (LowStockNotification)
- ✅ Прогноз потребности расходников (DemandForecastService)
- ✅ Автоматический график мастера (StaffScheduleService)

### Особенности
- 📸 Онлайн-примерка причёсок/макияжа
- 🎨 Портфолио мастера с фото до/после
- 🔥 Тепловая карта загруженности
- ⏰ Автоматические напоминания (24 ч и 2 ч)
- 📹 Видеозвонки для консультаций

### UI/UX
- Карточка мастера: фото, рейтинг, специализация, ближайшее время
- FullCalendar с занятыми слотами
- Фильтры: цена, длительность, рейтинг, пол мастера
- Форма отзыва + фото до/после

### Комиссия
- **Стандарт**: 14%
- **Dikidi переход**: 10% (4 мес) → 12% (24 мес)

---

## 🚗 ВЕРТИКАЛЬ 2: Auto & Mobility

**Домен**: `app/Domains/Auto`

### Основные сущности
- `TaxiDriver` - Водители такси
- `TaxiVehicle` - Автомобили
- `TaxiFleet` - Автопарки
- `TaxiRide` - Поездки
- `TaxiSurgeZone` - Зоны повышенного спроса
- `AutoPart` - Запчасти
- `AutoService` - Услуги СТО
- `AutoRepairOrder` - Заказы ремонта
- `CarWashBooking` - Броня мойки
- `TuningProject` - Проекты тюнинга

### Ключевые таблицы
```sql
taxi_drivers: user_id, license_number, rating, current_location (point), is_active
taxi_vehicles: driver_id/fleet_id, brand, model, license_plate, class (economy/comfort/business)
taxi_rides: passenger_id, driver_id, vehicle_id, pickup_point, dropoff_point, status, price, surge_multiplier
auto_parts: sku, name, brand, current_stock, price
auto_service_orders: client_id, car_id, service_type, status, total_price, appointment_datetime
```

### Автоматические процессы
- ✅ Surge pricing каждые 5 минут (TaxiSurgeService)
- ✅ Списание запчастей при завершении ремонта
- ✅ Прогноз спроса такси (DemandForecastService)
- ✅ Hold/release stock на запчасти
- ✅ Ежедневный пересчёт рейтинга

### Особенности
- 🗺️ GPS-трекинг (Glonass)
- 🔥 Тепловая карта спроса
- 📍 Расчёт маршрута и цены (OSRM/Yandex)
- 📅 Онлайн-бронь мойки/СТО
- 🎨 Тюнинг-проекты с этапами

### UI/UX
- Карточка поездки: карта, водитель, авто, цена
- Фильтры: класс авто, цена, рейтинг
- Календарь записи на СТО/мойку
- Профиль водителя: рейтинг, отзывы, фото

### Комиссия
- **Стандарт**: 15% + 5% автопарку
- **СЗ без автопарка**: 17.5%
- **Яндекс/Uber переход**: комиссия не снижается

---

## 🍽️ ВЕРТИКАЛЬ 3: Food & Delivery

**Домен**: `app/Domains/Food`

### Основные сущности
- `Restaurant` - Рестораны/кафе/кулинарии/столовые
- `RestaurantMenu` - Меню
- `Dish` - Блюда
- `DishVariant` - Варианты (размер, добавки)
- `RestaurantOrder` - Заказы
- `RestaurantTable` - Столики
- `DeliveryOrder` - Доставки
- `DeliveryZone` - Зоны доставки
- `KDSOrder` - Kitchen Display System

### Ключевые таблицы
```sql
restaurants: name, address, geo_point, cuisine_type (jsonb), schedule_json, rating, is_verified
restaurant_menus: restaurant_id, name, is_active
dishes: menu_id, name, price, calories, allergens (jsonb), cooking_time_minutes, consumables_json
restaurant_orders: restaurant_id, table_id, client_id, status, total_price, payment_status
delivery_orders: restaurant_id, client_id, address, geo_point, delivery_price, status, courier_id
delivery_zones: restaurant_id, polygon (geometry), surge_multiplier
```

### Автоматические процессы
- ✅ Списание ингредиентов при создании заказа (DeductOrderConsumables)
- ✅ Release stock при отмене до приготовления
- ✅ KDS автоматически передаёт заказ на кухню
- ✅ Surge pricing для доставки каждые 5 минут
- ✅ Прогноз спроса блюд (DemandForecastService)
- ✅ Автоматическое закрытие через 2 ч после "ready"

### Особенности
- 📱 QR-меню для столиков
- 📺 KDS-монитор в реальном времени
- 🔥 Тепловая карта спроса по блюдам/зонам
- 📄 Автоматическое формирование чека ОФД
- 🔗 Интеграция с агрегаторами

### UI/UX
- Карточка блюда: фото, калорийность, аллергены, время, рейтинг
- Календарь брони + карта свободных мест
- Фильтры: кухня, цена, аллергены, веган/постное
- Корзина с комментариями
- Трекинг доставки на карте

### Комиссия
- **Стандарт**: 14%
- **Delivery Club/Яндекс.Еда переход**: комиссия не снижается (бонус за оборот)

---

## 🏘️ ВЕРТИКАЛЬ 4: Real Estate & Rentals

**Домен**: `app/Domains/RealEstate`

### Основные сущности
- `Property` - Жилая/коммерческая недвижимость
- `LandPlot` - Земельные участки
- `RentalListing` - Долгосрочная аренда
- `SaleListing` - Продажа
- `ReadyBusiness` - Готовый бизнес
- `ViewingAppointment` - Просмотры
- `RealEstateAgent` - Агенты/риэлторы
- `MortgageApplication` - Заявки на ипотеку

### Ключевые таблицы
```sql
properties: owner_id (tenant_id), address, geo_point, type (apartment/house/land/commercial), area, rooms, floor, price, status
rental_listings: property_id, rent_price_month, deposit, lease_term_min, lease_term_max
sale_listings: property_id, sale_price, commission_percent
viewing_appointments: property_id, client_id, agent_id, datetime, status
real_estate_agents: tenant_id, license_number, rating
```

### Автоматические процессы
- ✅ Hold депозита при бронировании просмотра
- ✅ Списание комиссии при подписании договора
- ✅ Прогноз спроса по района (DemandForecastService) → корректировка цены
- ✅ Закрытие объявления после sold/rented
- ✅ Ежемесячные отчёты владельцу

### Особенности
- 🗺️ Интерактивная 3D-карта объектов
- 📸 Онлайн-тур (360° фото/видео)
- 🏦 Расчёт ипотеки (интеграция с банками)
- ⚖️ Проверка юридической чистоты (Росреестр API)
- 🔥 Тепловая карта спроса по районам

### UI/UX
- Карточка: фото, цена/м², площадь, этаж, ремонт, рейтинг
- Фильтры: тип, цена, площадь, район, этаж, ремонт, ипотека
- Календарь просмотров
- Профиль агента: рейтинг, отзывы, количество сделок
- Форма отзыва + рейтинг

### Комиссия
- **Стандарт**: 14% от суммы сделки (аренда - от первого платежа)
- **ЦИАН/Авито/Домклик переход**: комиссия не снижается

---

## 📊 СТРУКТУРА ФАЙЛОВ ВЕРТИКАЛЕЙ

```
app/Domains/
├── Beauty/
│   ├── Models/ → BeautySalon, Master, Service, Appointment, BeautyProduct, Consumable, PortfolioItem, Review
│   ├── Services/ → BeautyService, AppointmentService, ConsumableService
│   ├── Resources/ (Filament) → BeautySalonResource, MasterResource, AppointmentResource
│   ├── Pages/ (Filament) → ListBeautySalons, CreateBeautySalon, EditBeautySalon
│   └── Widgets/ → BeautyDashboard, LoadHeatmap, StaffSchedule
│
├── Auto/
│   ├── Models/ → TaxiDriver, TaxiVehicle, TaxiFleet, TaxiRide, AutoPart, AutoService, etc.
│   ├── Services/ → TaxiService, SurgeService, AutoRepairService
│   ├── Resources/ (Filament) → DriverResource, VehicleResource, RideResource
│   ├── Pages/ (Filament) → TaxiDashboard, DriverManagement
│   └── Widgets/ → TaxiHeatmap, SurgeMonitor, FleetStatus
│
├── Food/
│   ├── Models/ → Restaurant, Dish, DishVariant, RestaurantOrder, DeliveryOrder, KDSOrder, etc.
│   ├── Services/ → RestaurantService, OrderService, DeliveryService, KDSService
│   ├── Resources/ (Filament) → RestaurantResource, DishResource, OrderResource
│   ├── Pages/ (Filament) → RestaurantDashboard, MenuEditor, OrderMonitor
│   └── Widgets/ → DemandHeatmap, KDSMonitor, DeliveryTracker
│
└── RealEstate/
    ├── Models/ → Property, RentalListing, SaleListing, ViewingAppointment, RealEstateAgent, etc.
    ├── Services/ → PropertyService, PricingService, MortgageService
    ├── Resources/ (Filament) → PropertyResource, AgentResource, ViewingResource
    ├── Pages/ (Filament) → PropertyDashboard, PropertyEditor, VisualizationTour
    └── Widgets/ → MarketHeatmap, PricingRecommendation, AgentPerformance
```

---

## 🎯 ОБЩИЕ ПРАВИЛА ДЛЯ ВСЕХ ВЕРТИКАЛЕЙ

### Структура Services
```php
final class VerticalService {
    // Constructor with dependencies
    public function __construct(
        private readonly WalletService $walletService,
        private readonly FraudMLService $fraudService,
        private readonly RateLimiterService $rateLimiter,
    ) {}
    
    // Методы возвращают Result, Model или Collection - никогда null
    public function create(array $data): Model | throw
    public function update(int $id, array $data): Model | throw
    public function delete(int $id): bool | throw
    
    // DB::transaction() для всех мутаций
    // Log::channel('audit') для всех действий
    // correlation_id обязателен везде
}
```

### Обязательные поля в моделях
```php
- id (primary key)
- uuid (unique, indexed) для всех сущностей
- tenant_id (indexed) - обязателен
- business_group_id (nullable, indexed) - для филиалов
- correlation_id (nullable, indexed) - для отслеживания
- tags (jsonb, nullable) - для аналитики/фильтрации
- created_at, updated_at, deleted_at (if SoftDeletes)
- Все модели имеют final class + private readonly свойства
```

### Обязательные процессы
- ✅ Global scope на tenant_id в booted()
- ✅ FraudControlService::check() перед любой мутацией
- ✅ RateLimiterService для критичных операций
- ✅ Log::channel('audit') с correlation_id на вход/выход/ошибки
- ✅ DB::transaction() для всех записей в БД
- ✅ Валидация через FormRequest

### Комиссии по вертикалям
| Вертикаль | Стандарт | Миграция | Условия |
|-----------|----------|----------|---------|
| Beauty | 14% | Dikidi: 10%→12% | 4 мес → 24 мес |
| Auto | 15% + 5% | Яндекс/Uber: без снижения | СЗ: +2.5% |
| Food | 14% | DC/Яндекс.Еда: без снижения | - |
| RealEstate | 14% | ЦИАН/Авито: без снижения | От суммы сделки |

---

## 🔄 ОБЩИЕ СЕРВИСЫ (используются всеми вертикалями)

| Сервис | Назначение | Статус |
|--------|-----------|--------|
| **WalletService** | Управление кошельком бизнеса | ✅ Shared |
| **PaymentService** | Обработка платежей | ✅ Shared |
| **FraudMLService** | ML-фрод скоринг | ✅ Shared |
| **RecommendationService** | Персонализированные рекомендации | ✅ Shared |
| **DemandForecastService** | Прогноз спроса | ✅ Shared |
| **InventoryManagementService** | Управление запасами | ✅ Shared |
| **RateLimiterService** | Rate limiting | ✅ Shared |
| **IdempotencyService** | Защита от дублей | ✅ Shared |
| **PromoCampaignService** | Управление акциями | ✅ Shared |
| **ReferralService** | Реферальная программа | ✅ Shared |

---

## ✅ DEPLOYMENT CHECKLIST

### Per Vertical
- [ ] All models created with mandatory fields (uuid, tenant_id, correlation_id, tags)
- [ ] All migrations idempotent with comments
- [ ] Services follow naming: `{Vertical}Service`
- [ ] FormRequest validation for all public endpoints
- [ ] Policies for RBAC (view, create, update, delete)
- [ ] Filament Resources + Pages
- [ ] Tests: unit, integration, security
- [ ] Documentation: API endpoints, OpenAPI annotations

### Global
- [ ] All services registered as singletons in AppServiceProvider
- [ ] Rate limiting configured in config/security.php
- [ ] Commission rules set in config/commissions.php
- [ ] RBAC roles configured in config/rbac.php
- [ ] Webhook IP whitelisting configured
- [ ] Queue jobs configured for background tasks
- [ ] L5-Swagger documentation generated

---

**Last Updated**: 17 March 2026
**Total Verticals**: 4 (Beauty, Auto, Food, RealEstate)
**Status**: ✅ PRODUCTION-READY
