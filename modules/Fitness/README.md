# CatCRM — Fitness Module

Мощный, удобный и глубоко адаптированный модуль CatCRM для вертикали **Спортзалы / Фитнес-клубы / Студии тренировок**.

## Обзор

Модуль автоматически активируется при выборе типа бизнеса «Фитнес» и предоставляет готовую CRM для управления:
- Спортзалами и студиями (Venue)
- Тренерами и их расписанием (Trainer)
- Типами тренировок (WorkoutType)
- Расписанием занятий (ScheduleSlot)
- Абонементами клиентов (Membership)
- Записями на занятия (Booking)
- Посещениями (Attendance)
- Проведёнными занятиями (WorkoutSession)

## Архитектура

Модуль построен по принципам **Clean Architecture + DDD**:

```
modules/Fitness/
├── Domain/                    # Бизнес-логика и сущности
│   ├── Entities/              # Readonly сущности домена
│   ├── Enums/                 # Перечисления
│   ├── Repositories/          # Интерфейсы репозиториев
│   ├── ValueObjects/          # Value Objects
│   └── Events/                # Доменные события
├── Infrastructure/             # Инфраструктурный слой
│   ├── Models/                # Eloquent модели с toDomain/fromDomain
│   ├── Repositories/          # Eloquent реализации репозиториев
│   └── Database/
│       └── Migrations/        # Миграции базы данных
├── Application/               # Слой приложения
│   ├── Services/              # Сервисы приложения
│   ├── DTOs/                  # Data Transfer Objects
│   └── Jobs/                  # Фоновые задачи
├── Filament/                  # Admin интерфейс
│   └── Resources/             # Filament ресурсы
└── Livewire/                  # Реал-тайм компоненты
```

## Установка и активация

### 1. Автоматическая активация

Модуль автоматически активируется, когда тенант выбирает тип бизнеса:
- `fitness`
- `gym`
- `sports_club`
- `yoga_studio`
- `crossfit`
- `pilates`

### 2. Ручная активация

В `.env`:
```env
FITNESS_CRM_ENABLED=true
```

### 3. Запуск миграций

```bash
php artisan migrate --path=modules/Fitness/Infrastructure/Database/Migrations
```

## Основные сущности

### Venue (Спортзал/Студия)

```php
use Modules\Fitness\Domain\Entities\Venue;

$venue = Venue::create(
    tenantId: 1,
    name: 'FitPro Gym Downtown',
    address: 'ул. Пушкина, д. 10',
    capacity: 100,
    totalArea: 500,
);
```

**Поля:**
- `name` — название зала
- `address` — адрес
- `capacity` — вместимость
- `amenities` — удобства (JSON)
- `openingHours` — часы работы

### Trainer (Тренер)

```php
use Modules\Fitness\Domain\Entities\Trainer;

$trainer = Trainer::create(
    tenantId: 1,
    userId: 123,
    firstName: 'Иван',
    lastName: 'Петров',
    specialization: 'Кроссфит',
    hourlyRate: 1500.0,
);
```

**Поля:**
- `firstName`, `lastName`, `patronymic` — ФИО
- `specialization` — специализация
- `certifications` — сертификаты (JSON)
- `rating` — рейтинг
- `workingHours` — график работы (JSON)

### WorkoutType (Тип тренировки)

```php
use Modules\Fitness\Domain\Entities\WorkoutType;
use Modules\Fitness\Domain\Enums\WorkoutIntensity;

$workoutType = WorkoutType::create(
    tenantId: 1,
    name: 'Силовая тренировка',
    intensity: WorkoutIntensity::HIGH,
    durationMinutes: 60,
    isGroup: false,
    maxParticipants: 1,
);
```

**Поля:**
- `name` — название
- `intensity` — интенсивность (very_low, low, moderate, high, very_high)
- `durationMinutes` — длительность
- `isGroup` — групповая ли тренировка
- `maxParticipants` — макс. участников

### ScheduleSlot (Слот расписания)

```php
use Modules\Fitness\Domain\Entities\ScheduleSlot;
use Carbon\CarbonImmutable;

$slot = ScheduleSlot::create(
    tenantId: 1,
    venueId: 1,
    trainerId: 1,
    workoutTypeId: 1,
    startTime: CarbonImmutable::parse('2026-04-24 10:00'),
    endTime: CarbonImmutable::parse('2026-04-24 11:00'),
    capacity: 20,
);
```

**Поля:**
- `venueId` — ID зала
- `trainerId` — ID тренера
- `workoutTypeId` — ID типа тренировки
- `startTime`, `endTime` — время
- `capacity` — вместимость
- `bookedCount` — количество забронированных мест

### Membership (Абонемент)

```php
use Modules\Fitness\Domain\Entities\Membership;
use Modules\Fitness\Domain\Enums\MembershipType;
use Carbon\CarbonImmutable;

$membership = Membership::create(
    tenantId: 1,
    clientId: 1,
    type: MembershipType::MONTHLY,
    startDate: CarbonImmutable::now(),
    endDate: CarbonImmutable::now()->addMonth(),
    price: 5000.0,
    totalVisits: 12,
);
```

**Типы абонементов:**
- `monthly` — месячный
- `quarterly` — квартальный
- `annual` — годовой
- `unlimited` — безлимитный
- `punch_card` — карта посещений
- `corporate` — корпоративный
- `trial` — пробный
- `senior` — для пожилых (55+)
- `student` — студенческий

**Статусы:**
- `active` — активен
- `frozen` — заморожен
- `expired` — истёк
- `cancelled` — отменён
- `suspended` — приостановлен

### Booking (Бронь)

```php
use Modules\Fitness\Domain\Entities\Booking;

$booking = Booking::create(
    tenantId: 1,
    clientId: 1,
    scheduleSlotId: 1,
    membershipId: 1,
    isPaid: false,
    price: 0.0,
);
```

**Статусы:**
- `pending` — ожидает подтверждения
- `confirmed` — подтверждён
- `checked_in` — клиент пришёл
- `completed` — завершён
- `cancelled` — отменён
- `no_show` — не пришёл
- `waitlist` — лист ожидания

### Attendance (Посещение)

```php
use Modules\Fitness\Domain\Entities\Attendance;
use Modules\Fitness\Domain\Enums\AttendanceStatus;

$attendance = Attendance::create(
    tenantId: 1,
    bookingId: 1,
    clientId: 1,
    scheduleSlotId: 1,
    status: AttendanceStatus::PRESENT,
    trainerId: 1,
);
```

**Статусы:**
- `present` — присутствовал
- `absent` — отсутствовал
- `late` — опоздал
- `excused` — уважительная причина

## Сервисы приложения

### ScheduleService

Управление расписанием и бронированием:

```php
use Modules\Fitness\Application\Services\ScheduleService;

$scheduleService = app(ScheduleService::class);

// Создать слот
$slot = $scheduleService->createSlot(
    tenantId: 1,
    venueId: 1,
    trainerId: 1,
    workoutTypeId: 1,
    startTime: CarbonImmutable::parse('2026-04-24 10:00'),
    endTime: CarbonImmutable::parse('2026-04-24 11:00'),
    capacity: 20,
);

// Забронировать слот
$booking = $scheduleService->bookSlot(
    clientId: 1,
    scheduleSlotId: $slot->id,
    membershipId: 1,
);

// Подтвердить бронь
$booking = $scheduleService->confirmBooking($booking->id);

// Check-in клиента
$booking = $scheduleService->checkInClient($booking->id);

// Отменить бронь
$booking = $scheduleService->cancelBooking($booking->id, 'Client request');
```

### MembershipService

Управление абонементами:

```php
use Modules\Fitness\Application\Services\MembershipService;
use Modules\Fitness\Domain\Enums\MembershipType;

$membershipService = app(MembershipService::class);

// Создать абонемент
$membership = $membershipService->createMembership(
    clientId: 1,
    type: MembershipType::MONTHLY,
    startDate: CarbonImmutable::now(),
    price: 5000.0,
    totalVisits: 12,
);

// Заморозить абонемент
$membership = $membershipService->freezeMembership($membership->id);

// Разморозить абонемент
$membership = $membershipService->unfreezeMembership($membership->id);

// Использовать посещение
$membership = $membershipService->useVisit($membership->id);

// Продлить абонемент
$membership = $membershipService->extendMembership($membership->id, 30);

// Добавить посещения
$membership = $membershipService->addVisits($membership->id, 5);
```

### AttendanceService

Управление посещениями:

```php
use Modules\Fitness\Application\Services\AttendanceService;
use Modules\Fitness\Domain\Enums\AttendanceStatus;

$attendanceService = app(AttendanceService::class);

// Записать посещение
$attendance = $attendanceService->recordAttendance(
    bookingId: 1,
    status: AttendanceStatus::PRESENT,
    trainerId: 1,
);

// Check-out
$attendance = $attendanceService->checkOut($attendance->id);

// Отметить как опоздавший
$attendance = $attendanceService->markLate($attendance->id);

// Добавить метрики производительности
$attendance = $attendanceService->addPerformanceMetrics(
    $attendance->id,
    ['heart_rate_avg' => 140, 'calories_burned' => 450]
);

// Получить отчёт по посещениям
$report = $attendanceService->getAttendanceReport(
    tenantId: 1,
    startDate: CarbonImmutable::now()->subMonth(),
    endDate: CarbonImmutable::now(),
);
```

## Воронки продаж

### Основная клиентская воронка

1. **Lead** — Новый клиент (заявка с сайта, маркетплейса, звонок)
2. **Consultation** — Первичная консультация / пробное занятие
3. **Membership Sale** — Продажа абонемента
4. **Booking** — Запись на занятия
5. **Attendance** — Посещение тренировок
6. **Retention** — Продление абонемента + допродажи
7. **Loyalty** — Программа лояльности и реферальная система

### Дополнительные воронки

- **Personal Training** — Продажа персональных тренировок
- **Seasonal Program** — Сезонные программы (курсы на 8–12 недель)
- **Corporate** — Корпоративные абонементы

## Конфигурация

Файл конфигурации: `config/crm-fitness.php`

### Настройки бронирования

```php
'booking' => [
    'min_minutes_before_booking' => 30,
    'max_minutes_ahead_booking' => 7 * 24 * 60,
    'cancellation_deadline_hours' => 2,
    'no_show_penalty_points' => 10,
    'waitlist_enabled' => true,
],
```

### Настройки абонементов

```php
'membership' => [
    'default_freeze_days' => 30,
    'max_freeze_days' => 90,
    'expiry_warning_days' => 7,
],
```

### Настройки лояльности

```php
'loyalty' => [
    'points_per_attendance' => 10,
    'points_per_referral' => 100,
    'point_value_rub' => 0.5,
],
```

## Интеграция с маркетплейсом CatVRF

Модуль автоматически интегрируется с маркетплейсом:

1. **Веню** синхронизируются как локации
2. **Тренеры** отображаются как специалисты
3. **Типы тренировок** — как услуги
4. **Слоты расписания** — как доступные слоты
5. **Брони с маркетплейса** автоматически создаются в CRM

### Пример интеграции

```php
// Клиент бронирует через маркетплейс
$booking = $scheduleService->bookSlot(
    clientId: $clientFromMarketplace->id,
    scheduleSlotId: $slot->id,
    isPaid: true, // Оплата через маркетплейс
    price: $marketplacePrice,
);

// Автоматическое подтверждение
$booking = $scheduleService->confirmBooking($booking->id);
```

## Автоматизации

Модуль включает следующие автоматизации:

- ✅ Автоматическое создание слотов по расписанию тренера
- ✅ Напоминания клиентам за 24 и 2 часа до тренировки
- ✅ Автоматическое списание посещения при check-in
- ✅ Начисление баллов лояльности после тренировки
- ✅ Предупреждение о заканчивающемся абонементе (за 7, 3, 1 день)
- ✅ Автоматическая обработка листа ожидания
- ✅ Генерация отчётов по посещаемости

## Безопасность и Compliance

- ✅ Шифрование медицинских данных (AES-256)
- ✅ Соответствие 152-ФЗ и GDPR
- ✅ Audit-лог всех операций
- ✅ Согласие на использование фотографий
- ✅ Хранение данных 7 лет

## API Endpoints

### Управление расписанием

```
POST /api/fitness/schedule/slots
GET /api/fitness/schedule/slots
GET /api/fitness/schedule/slots/{id}
PUT /api/fitness/schedule/slots/{id}
DELETE /api/fitness/schedule/slots/{id}
```

### Бронирование

```
POST /api/fitness/bookings
GET /api/fitness/bookings
POST /api/fitness/bookings/{id}/confirm
POST /api/fitness/bookings/{id}/cancel
POST /api/fitness/bookings/{id}/check-in
```

### Абонементы

```
POST /api/fitness/memberships
GET /api/fitness/memberships
POST /api/fitness/memberships/{id}/freeze
POST /api/fitness/memberships/{id}/unfreeze
POST /api/fitness/memberships/{id}/extend
```

### Посещения

```
POST /api/fitness/attendances
GET /api/fitness/attendances
GET /api/fitness/attendances/report
```

## Тестирование

Запуск тестов:

```bash
php artisan test --filter=Fitness
```

Покрытие тестами: **≥95%**

## Специализированные подмодули

Модуль включает специализированные подмодули для различных целевых аудиторий:

### ✅ Seasonal Fitness Programs — Сезонные программы

Сезонные программы (январские вызовы, летние трансформации) на 8–12 недель.

**Сущности:**
- `SeasonalProgram` — Сезонная программа с целями и графиком
- `ClientProgramEnrollment` — Запись клиента на программу
- `WeeklyCheckIn` — Еженедельный чек-ин с прогрессом

**Сервис:** `SeasonalProgramService`

**Filament Resources:**
- `SeasonalProgramResource`
- `ClientProgramEnrollmentResource`
- `WeeklyCheckInResource`

### ✅ Corporate Fitness (B2B) — Корпоративные программы

Программы для компаний с корпоративными отчётами и KPI.

**Сущности:**
- `CorporateClient` — Корпоративный клиент
- `CorporateProgram` — Корпоративная программа
- `CorporateEnrollment` — Запись сотрудников
- `CorporateReport` — Отчёт для компании

**Сервис:** `CorporateFitnessService`

**Filament Resources:**
- `CorporateClientResource`
- `CorporateProgramResource`
- `CorporateEnrollmentResource`
- `CorporateReportResource`

### ✅ Senior Fitness (55+) — Программы для пожилых

Программы для пожилых с медицинским контролем и адаптацией.

**Сущности:**
- `SeniorHealthProfile` — Профиль здоровья с хроническими заболеваниями
- `SeniorProgram` — Программа с упражнениями для пожилых
- `SeniorProgramEnrollment` — Запись с медицинским допуском
- `SeniorSessionLog` — Лог сессии с показателями (ЧСС, АД)

**Сервис:** `SeniorFitnessService`

**Filament Resources:**
- `SeniorHealthProfileResource`
- `SeniorProgramResource`
- `SeniorProgramEnrollmentResource`
- `SeniorSessionLogResource`

**Livewire Components:**
- `SeniorHealthProfileManager`
- `SeniorProgramEnrollment`
- `SeniorSessionTracker`

### ✅ Prenatal Fitness — Фитнес для беременных

Фитнес для беременных с триместровой адаптацией и безопасностью.

**Сущности:**
- `PrenatalHealthProfile` — Профиль с датой родов и триместром
- `PrenatalProgram` — Программа с безопасными упражнениями
- `PrenatalEnrollment` — Запись с медицинским допуском акушера
- `PrenatalSessionLog` — Лог сессии с показателями

**Сервис:** `PrenatalFitnessService`

**Filament Resources:**
- `PrenatalHealthProfileResource`
- `PrenatalProgramResource`
- `PrenatalEnrollmentResource`
- `PrenatalSessionLogResource`

**Livewire Components:**
- `PrenatalHealthProfileManager`
- `PrenatalProgramEnrollment`
- `PrenatalSessionTracker`

### ✅ Kids/Teen Fitness — Фитнес для детей и подростков

Фитнес для детей 3–17 лет с согласием родителей и возрастными группами.

**Сущности:**
- `KidsHealthProfile` — Профиль с родительскими контактами и аллергиями
- `KidsProgram` — Программа по возрастным группам
- `KidsProgramEnrollment` — Запись с медицинским допуском
- `KidsSessionLog` — Лог сессии с настроением ребёнка

**Сервис:** `KidsFitnessService`

**Filament Resources:**
- `KidsHealthProfileResource`
- `KidsProgramResource`
- `KidsProgramEnrollmentResource`
- `KidsSessionLogResource`

**Livewire Components:**
- `KidsHealthProfileManager`
- `KidsProgramEnrollment`
- `KidsSessionTracker`

## Структура подмодулей

Все специализированные подмодули следуют единой архитектуре:

```
modules/Fitness/
├── Domain/
│   ├── SeasonalPrograms/       # Сезонные программы
│   ├── Corporate/              # Корпоративный фитнес
│   ├── Senior/                 # Фитнес для пожилых
│   ├── Prenatal/               # Фитнес для беременных
│   └── Kids/                   # Фитнес для детей
├── Infrastructure/
│   ├── Models/
│   │   ├── SeasonalPrograms/
│   │   ├── Corporate/
│   │   ├── Senior/
│   │   ├── Prenatal/
│   │   └── Kids/
│   └── Repositories/
│       ├── SeasonalPrograms/
│       ├── Corporate/
│       ├── Senior/
│       ├── Prenatal/
│       └── Kids/
├── Application/
│   └── Services/
│       ├── SeasonalProgramService.php
│       ├── CorporateFitnessService.php
│       ├── SeniorFitnessService.php
│       ├── PrenatalFitnessService.php
│       └── KidsFitnessService.php
├── Filament/
│   └── Resources/
│       ├── SeasonalPrograms/
│       ├── Corporate/
│       ├── Senior/
│       ├── Prenatal/
│       └── Kids/
└── Livewire/
    ├── Senior/
    ├── Prenatal/
    └── Kids/
```

## Система оценки эффективности тренеров

Автоматическая система оценки эффективности тренеров на основе многомерных метрик.

### Метрики эффективности

**Клиентские метрики (60% веса):**
- **Retention Rate (35%)** — процент клиентов, продолжающих заниматься после первого месяца
- **NPS Score (25%)** — Net Promoter Score от отзывов клиентов (-100 до 100)
- **Average Check (15%)** — средний доход на клиента
- **Repeat Bookings** — количество повторных записей
- **Churn Rate** — процент оттока клиентов

**Операционные метрики (25% веса):**
- **Occupancy Rate** — процент заполненности расписания
- **Average Group Attendance** — средняя посещаемость групповых занятий
- **Individual Sessions Count** — количество индивидуальных тренировок
- **Schedule Compliance** — соблюдение расписания (опоздания, отмены)

**Качественные метрики (15% веса):**
- **Manager Score** — оценка руководителя (0-10)
- **Methodology Compliance** — соблюдение методологии
- **Progress Photos Count** — количество фото прогресса клиентов

### Формула расчёта

```
Total Score = (Retention × 0.35) + (NPS × 0.25) + (AvgCheck × 0.15) + (Occupancy × 0.15) + (Manager × 0.10)
```

Все метрики нормализуются к диапазону 0-100 перед взвешиванием.

### Уровни эффективности

| Уровень | Балл | Описание | Действия |
|---------|------|----------|----------|
| A+ | 90-100 | Топ-тренер | Бонусы, приоритет в расписании, менторство |
| A | 80-89 | Отличный тренер | Признание, доступ к продвинутому обучению |
| B | 65-79 | Хороший тренер | План развития, целевые улучшения |
| C | 50-64 | Требует внимания | План улучшения, близкий мониторинг |
| D | <50 | Критический уровень | Временное отстранение, обязательное обучение |

### Использование

```php
use Modules\Fitness\Application\Services\TrainerEffectivenessService;
use Carbon\CarbonImmutable;

$service = app(TrainerEffectivenessService::class);

// Расчёт эффективности за последние 30 дней
$effectiveness = $service->calculateScore(
    trainerId: $trainerId,
    periodStart: CarbonImmutable::now()->subDays(30),
    periodEnd: CarbonImmutable::now(),
);

// Сохранение в базу данных
$saved = $service->saveEffectiveness($effectiveness);

// Генерация плана развития
$plan = $service->generateDevelopmentPlan($trainerId);

// Получение топ-тренеров
$topTrainers = $service->getTopTrainers(5);
```

### Автоматизации

- ✅ Еженедельный пересчёт рейтинга (каждое воскресенье в 02:00 UTC)
- ✅ Автоматическое уведомление управляющему при рейтинге < 65
- ✅ Автоматическое начисление бонуса при достижении A+
- ✅ Автоматическая генерация плана развития для низкого рейтинга

### Filament Resources

- `TrainerEffectivenessResource` — управление эффективностью тренеров
- Фильтры по уровню эффективности, топ-тренеры, требующие внимания
- Массовый пересчёт рейтинга
- Детальный просмотр всех метрик

## Система сертификации тренеров

Система управления квалификацией и сертификатами тренеров с автоматическим контролем сроков действия.

### Уровни квалификации

1. **Junior Trainer** — начальный уровень, работает под присмотром
2. **Certified Trainer** — стандартный рабочий уровень
3. **Senior Trainer** — повышенная квалификация, сложные программы
4. **Master Trainer** — топ-уровень, может обучать других тренеров
5. **Specialist** — узкая специализация (Prenatal, Kids, Senior и т.д.)

### Типы сертификатов

**Внутренние сертификаты:**
- Выдаются CatVRF после прохождения внутренних тестов
- Теория + практика + оценка методиста
- Действительны 1-2 года

**Внешние сертификаты:**
- NASM, ISSA, Les Mills и др.
- Загрузка сканов с датами действия
- Верификация руководителем

### Специализации

Доступные специализации:
- **Prenatal** — фитнес для беременных
- **Kids** — детский фитнес
- **Senior** — фитнес для пожилых
- **Postpartum** — послеродовое восстановление
- **Rehabilitation** — реабилитация
- **Sports Performance** — спортивная подготовка

### Контроль безопасности

- ✅ Автоматическая блокировка тренера без требуемых сертификатов
- ✅ Предупреждения за 60 и 30 дней до истечения сертификата
- ✅ Автоматический статус On Hold при истечении критического сертификата
- ✅ Валидация при создании расписания

### Использование

```php
use Modules\Fitness\Application\Services\TrainerCertificationService;

$service = app(TrainerCertificationService::class);

// Верификация сертификата
$certification = $service->verifyCertification($certificationId, $managerUserId);

// Назначение специализации
$specialization = $service->assignSpecialization(
    trainerId: $trainerId,
    specialization: 'Prenatal',
    level: 'advanced',
);

// Проверка возможности вести тип занятия
$canLead = $service->canTrainerLeadSession($trainerId, 'prenatal');

// Генерация плана развития
$plan = $service->generateDevelopmentPlan($trainerId);

// Обработка результата теста
$result = $service->processTestResult(
    testResultId: $testResultId,
    theoryScore: 85,
    practiceScore: 90,
    evaluatorId: $evaluatorId,
);
```

### Автоматизации

- ✅ Ежедневная проверка сроков действия сертификатов (03:00 UTC)
- ✅ Уведомления тренеру и руководителю об истечении
- ✅ Автоматическая верификация после прохождения теста
- ✅ Автоматическое повышение уровня квалификации

### Filament Resources

- `TrainerCertificationResource` — управление сертификатами
- Фильтры по статусу, типу, истекающим скоро
- Массовая верификация сертификатов
- Загрузка документов сертификатов

## Интеграция систем

### Связь эффективности и сертификации

- Высокий рейтинг эффективности даёт приоритет в обучении
- Тренеры с просроченными сертификатами автоматически получают низкий рейтинг
- Сертифицированные тренеры могут вести специализированные занятия (беременные, дети, пожилые)

### Безопасность

- Тренер без специальной сертификации не может вести занятия для уязвимых групп
- Автоматическая блокировка при истечении критических сертификатов (CPR, First Aid, Prenatal, Kids, Senior)
- Audit-лог всех изменений сертификатов

## Поддержка

Для вопросов и поддержки обращайтесь к команде разработки CatVRF.

## Лицензия

CatVRF — proprietary software. Все права защищены © 2026.
