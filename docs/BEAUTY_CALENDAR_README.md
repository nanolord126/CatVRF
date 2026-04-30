# Система календаря записи для Beauty Vertical

**Версия:** 1.0  
**Дата:** 24.04.2026  
**Модуль:** Beauty (CatVRF)

## Обзор

Система календаря записи предоставляет удобное управление расписанием для бьюти-салонов, барбершопов, студий маникюра/педикюра, косметологии и SPA. Календарь поддерживает три вида: для администратора, для мастера и для онлайн-записи клиентов.

## Архитектура

### Компоненты

1. **Модели**
   - `MasterSchedule` — рабочее время мастера (дни, часы, перерывы, отпуска)
   - `BlockedSlot` — заблокированные слоты (уборка, обучение, технический перерыв)
   - `Appointment` — запись клиента (уже существовала)

2. **Livewire компоненты**
   - `AdminCalendar` — главный календарь для администратора/владельца
   - `MasterCalendar` — персональный календарь мастера (мобильный/планшетный вид)
   - `AppointmentBookingComponent` — онлайн-запись для клиентов (уже существовала)

3. **Сервисы**
   - `AppointmentService` — логика создания, перемещения и отмены записей

## Типы календарей

### 1. Главный календарь (AdminCalendar)

**Назначение:** Полный вид всего салона для администратора/владельца

**Возможности:**
- Показывает всех мастеров одновременно
- Фильтры: по мастеру, по статусу
- Drag & Drop для перемещения записей
- Цветовая индикация по статусу записи
- Создание новых записей кликом по слоту
- Массовые действия (перенос всех записей при отпуске мастера)

**Цветовая индикация:**
- 🟢 Зелёный — подтверждена и оплачена
- 🔵 Синий — подтверждена, но не оплачена
- 🟡 Жёлтый — ожидает подтверждения
- 🟠 Оранжевый — клиент в процессе услуги
- 🔴 Красный — отменена или неявка
- ⚫ Серый — заблокированный слот

**Использование:**
```blade
<livewire:beauty.admin-calendar />
```

### 2. Календарь мастера (MasterCalendar)

**Назначение:** Персональный календарь для мастера (мобильный/планшетный вид)

**Возможности:**
- Только записи конкретного мастера
- Крупный шрифт, удобный для работы на планшете
- Быстрые действия: «Клиент пришёл», «Услуга начата», «Завершена», «Не пришёл»
- Отображение времени до окончания текущей услуги
- Переключение между дневным и недельным видом

**Использование:**
```blade
<livewire:beauty.master-calendar />
```

### 3. Календарь онлайн-записи (AppointmentBookingComponent)

**Назначение:** Публичный вид для клиентов (на сайте / в приложении CatVRF)

**Возможности:**
- Показывает только свободные слоты
- Фильтр по услугам и мастерам
- Автоматическое предложение ближайших свободных окон
- Выбор мастера → выбор услуги → выбор свободного слота

**Использование:**
```blade
<livewire:beauty.appointment-booking-component />
```

## Миграции

### beauty_master_schedules

```php
Schema::create('beauty_master_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('master_id')->constrained('beauty_masters')->onDelete('cascade');
    $table->tinyInteger('day_of_week')->comment('0-6 (Sunday-Saturday)');
    $table->time('start_time')->comment('HH:MM:SS');
    $table->time('end_time')->comment('HH:MM:SS');
    $table->time('break_start_time')->nullable()->comment('HH:MM:SS');
    $table->time('break_end_time')->nullable()->comment('HH:MM:SS');
    $table->boolean('is_available')->default(true);
    $table->text('notes')->nullable()->comment('vacation, sick leave, etc.');
    $table->timestamps();
});
```

### beauty_blocked_slots

```php
Schema::create('beauty_blocked_slots', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('master_id')->nullable()->constrained('beauty_masters')->onDelete('cascade');
    $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('cascade');
    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->string('reason')->default('other')->comment('vacation, training, cleaning, sick_leave, other');
    $table->text('notes')->nullable();
    $table->boolean('is_recurring')->default(false);
    $table->string('recurrence_pattern')->nullable()->comment('weekly, monthly');
    $table->date('recurrence_end_date')->nullable();
    $table->timestamps();
});
```

## AppointmentService

### Методы

#### book(BookAppointmentDto $dto): Appointment
Создание новой записи с проверкой доступности слота и fraud-проверкой.

```php
$appointment = $service->book(new BookAppointmentDto(
    tenantId: 1,
    salonId: 1,
    masterId: 1,
    serviceId: 1,
    userId: 1,
    startsAt: '2026-04-25 10:00',
    correlationId: Str::uuid()->toString(),
    isB2b: false
));
```

#### moveAppointment(int $appointmentId, int $newMasterId, string $newDate, string $newTime, string $correlationId): Appointment
Перемещение записи на другой слот с проверкой конфликтов.

```php
$appointment = $service->moveAppointment(
    appointmentId: 1,
    newMasterId: 2,
    newDate: '2026-04-26',
    newTime: '14:00',
    correlationId: Str::uuid()->toString()
);
```

#### cancelAppointment(int $appointmentId, string $reason, string $correlationId): Appointment
Отмена записи с указанием причины.

```php
$appointment = $service->cancelAppointment(
    appointmentId: 1,
    reason: 'Клиент отменил запись',
    correlationId: Str::uuid()->toString()
);
```

#### updateStatus(int $appointmentId, string $status, string $correlationId): Appointment
Обновление статуса записи (для мастера).

```php
$appointment = $service->updateStatus(
    appointmentId: 1,
    status: 'in_progress',
    correlationId: Str::uuid()->toString()
);
```

## Статусы записей

| Статус | Описание | Цвет | Анимация |
|--------|----------|------|----------|
| `pending` | Ожидает подтверждения | Warning (жёлтый) | gentle-shake |
| `confirmed` | Подтверждена | Info (синий) | wave |
| `in_progress` | В процессе услуги | Primary (оранжевый) | wave |
| `completed` | Завершена | Success (зелёный) | success-pulse |
| `cancelled` | Отменена | Danger (красный) | danger-flash |
| `no_show` | Неявка | Secondary (серый) | danger-flash |

## Автоматизации

### Реализованные
- ✅ Автоматическая проверка доступности слота при создании записи
- ✅ Fraud-проверка перед каждой операцией
- ✅ Audit-логирование всех изменений
- ✅ Проверка пересечений слотов при перемещении

### Планируемые
- [ ] Автоматическое подтверждение записи при онлайн-оплате
- [ ] Напоминания клиенту за 24ч и 2ч до записи (Telegram / Push / Email)
- [ ] Автоматическое начисление баллов лояльности после завершения услуги
- [ ] Блокировка слота при создании записи (Redis + Lua)
- [ ] Реал-тайм обновления через Laravel Echo (WebSocket)

## Интеграция с единой системой статусов

Календарь использует единый компонент `status-badge` с анимациями:

```blade
<x-status-badge 
    :status="$appointment['status']"
    :label="..."
    :color="..."
    :icon="..."
/>
```

Это обеспечивает согласованную визуальную индикацию статусов во всех вертикалях CatVRF.

## Установка

### 1. Запуск миграций

```bash
php artisan migrate
```

Будут выполнены миграции:
- `2026_04_24_000001_create_beauty_master_schedules_table.php`
- `2026_04_24_000002_create_beauty_blocked_slots_table.php`

### 2. Регистрация маршрутов

Добавьте в `routes/web.php`:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/beauty/admin-calendar', AdminCalendar::class)->name('beauty.admin-calendar');
    Route::get('/beauty/master-calendar', MasterCalendar::class)->name('beauty.master-calendar');
});
```

### 3. Настройка расписания мастеров

Создайте рабочее расписание для каждого мастера:

```php
use App\Domains\Beauty\Models\MasterSchedule;

MasterSchedule::create([
    'tenant_id' => 1,
    'master_id' => 1,
    'day_of_week' => 1, // Monday
    'start_time' => '09:00:00',
    'end_time' => '18:00:00',
    'break_start_time' => '13:00:00',
    'break_end_time' => '14:00:00',
    'is_available' => true,
]);
```

### 4. Блокировка слотов

При необходимости заблокируйте слоты (отпуск, обучение):

```php
use App\Domains\Beauty\Models\BlockedSlot;

BlockedSlot::create([
    'tenant_id' => 1,
    'master_id' => 1,
    'start_time' => '2026-05-01 09:00:00',
    'end_time' => '2026-05-07 18:00:00',
    'reason' => 'vacation',
    'notes' => 'Отпуск',
]);
```

## Тестирование

### Запуск тестов

```bash
php artisan test --filter BeautyCalendarTest
```

### Покрытие тестами

- Создание записи
- Проверка пересечений слотов
- Перемещение записи
- Отмена записи
- Обновление статуса
- Проверка рабочего расписания
- Проверка заблокированных слотов

## Производительность

- Кэширование расписания в Redis (планируется)
- Индексы на `master_id`, `start_time`, `end_time`
- Оптимизированные запросы с eager loading
- WebSocket для real-time обновлений (планируется)

## Совместимость

- **Браузеры:** Chrome, Firefox, Safari, Edge (последние 2 версии)
- **Устройства:** Десктоп, планшет (кухня/мастер), мобильный
- **Filament:** 3.x
- **Livewire:** 3.x
- **Laravel:** 11.x

## Файлы

```
app/Domains/Beauty/
  Models/
    MasterSchedule.php                    # Модель расписания мастера
    BlockedSlot.php                       # Модель заблокированных слотов
  Domain/Services/
    AppointmentService.php                # Сервис записей (расширен)

app/Http/Livewire/Beauty/
  AdminCalendar.php                       # Компонент календаря администратора
  MasterCalendar.php                      # Компонент календаря мастера

resources/views/livewire/beauty/
  admin-calendar.blade.php                # Шаблон календаря администратора
  master-calendar.blade.php               # Шаблон календаря мастера

database/migrations/
  2026_04_24_000001_create_beauty_master_schedules_table.php
  2026_04_24_000002_create_beauty_blocked_slots_table.php

docs/
  BEAUTY_CALENDAR_README.md               # Этот файл
```

## Примеры использования

### Для администратора

```php
// Создание записи
$appointment = $service->book(new BookAppointmentDto(...));

// Перемещение записи на другой слот
$appointment = $service->moveAppointment(1, 2, '2026-04-26', '14:00', $correlationId);

// Отмена записи
$appointment = $service->cancelAppointment(1, 'Клиент отменил', $correlationId);
```

### Для мастера

```php
// Начать услугу
$appointment = $service->updateStatus(1, 'in_progress', $correlationId);

// Завершить услугу
$appointment = $service->updateStatus(1, 'completed', $correlationId);

// Отметить неявку
$appointment = $service->cancelAppointment(1, 'Клиент не пришёл', $correlationId);
```

## Будущие улучшения

- [ ] Интеграция с FullCalendar.js для улучшенного UI
- [ ] Реал-тайм синхронизация через WebSocket
- [ ] Автоматические напоминания (Telegram / Push / Email)
- [ ] Интеграция с лояльной программой
- [ ] Статистика загрузки мастеров
- [ ] Кастомизируемые цвета статусов
- [ ] Экспорт расписания в PDF/Excel
- [ ] Массовый перенос записей при отпуске мастера

## Поддержка

При проблемах с календарем:
1. Проверьте миграции (запустите `php artisan migrate:status`)
2. Убедитесь, что расписание мастера настроено корректно
3. Проверьте логи Laravel на ошибки
4. Очистите кэш: `php artisan cache:clear`

---

**Автор:** CatVRF Team  
**Лицензия:** Proprietary  
**Статус:** Production Ready
