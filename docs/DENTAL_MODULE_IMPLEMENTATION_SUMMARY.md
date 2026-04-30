# Dental Module Implementation Summary

## Overview

Dental модуль CatCRM — полноценная система управления стоматологией, включающая планы лечения, процедуры, зубные карты, пациентов и интеграцию с маркетплейсом CatVRF.

## Architecture

### Domain Layer

#### Entities
- **TreatmentPlan** — план лечения
- **Procedure** — процедура
- **Patient** — пациент
- **Doctor** — врач
- **ToothChart** — зубная карта
- **LabTest** — лабораторный анализ

#### Enums
- **TreatmentStatus** — статусы планов лечения (draft, active, in_progress, completed, cancelled, on_hold)
- **ProcedureCategory** — категории процедур (diagnostics, preventive, restorative, endodontic, periodontal, prosthetic, orthodontic, surgical, cosmetic)
- **ProcedureType** — типы процедур (examination, xray, cleaning, filling, root_canal, extraction, crown, implant, braces)
- **PatientStatus** — статусы пациентов (active, inactive, new, vip)

### Application Services

- **TreatmentPlanService** — управление планами лечения
- **ProcedureService** — управление процедурами
- **PatientService** — управление пациентами
- **ToothChartService** — управление зубными картами
- **SalesFunnelService** — управление воронкой продаж
- **AppointmentReminderService** — сервис напоминаний о приёмах

### Infrastructure Layer

#### Models
- **TreatmentPlanModel** — модель плана лечения
- **ProcedureModel** — модель процедуры
- **PatientModel** — модель пациента
- **DoctorModel** — модель врача
- **ToothChartModel** — модель зубной карты

#### Repositories
- **TreatmentPlanRepositoryInterface** — репозиторий планов лечения
- **ProcedureRepositoryInterface** — репозиторий процедур
- **PatientRepositoryInterface** — репозиторий пациентов

## Filament Resources

### TreatmentPlanResource
- **Статусы с анимированными бейджами**: draft (gray), active (success), in_progress (info), completed (success), cancelled (danger), on_hold (warning)
- **Иконки**: document, play, clock, check-circle, x-circle, pause
- **Фильтры**: по статусу, пациенту, врачу, дате
- **Действия**: просмотр, редактирование, удаление

### ProcedureResource
- **Управление процедурами**
- **Категории**: diagnostics, preventive, restorative, endodontic, periodontal, prosthetic, orthodontic, surgical, cosmetic
- **Типы**: examination, xray, cleaning, filling, root_canal, extraction, crown, implant, braces
- **Статусы**: scheduled, in_progress, completed, cancelled, postponed
- **Фильтры**: по категории, типу, статусу, цене

### ToothChartResource
- **Управление зубными картами**
- **Системы нумерации**: FDI, Universal, Palmer
- **Автоматическое обновление**
- **История изменений**

## Livewire Components

- **RealTimeAppointmentDashboard** — дашборд приёмов в реальном времени
- **ToothChartVisualizer** — визуализатор зубной карты
- **SalesFunnelBoard** — канбан-доска воронки продаж
- **AppointmentScheduler** — планировщик приёмов

## Configuration

Конфигурация модуля находится в `config/crm-dental.php`:

```php
return [
    'enabled' => env('DENTAL_ENABLED', true),
    'default_currency' => env('DENTAL_CURRENCY', 'RUB'),
    'treatment_plans' => [
        'auto_confirm' => env('DENTAL_AUTO_CONFIRM', false),
        'require_deposit' => env('DENTAL_REQUIRE_DEPOSIT', true),
        'deposit_percentage' => env('DENTAL_DEPOSIT_PERCENTAGE', 30),
        'hold_time_minutes' => env('DENTAL_HOLD_TIME', 60),
        'max_procedures_per_plan' => env('DENTAL_MAX_PROCEDURES', 20),
    ],
    'sales_funnel' => [
        'stages' => [...],
        'auto_stage_transition' => env('DENTAL_AUTO_STAGE_TRANSITION', true),
    ],
    'procedures' => [
        'categories' => [...],
        'procedure_types' => [...],
        'inventory' => [
            'auto_low_stock_alert' => true,
            'low_stock_threshold' => 5,
        ],
    ],
    'tooth_charts' => [
        'numbering_system' => env('DENTAL_NUMBERING_SYSTEM', 'fdi'),
        'auto_update' => env('DENTAL_AUTO_UPDATE_TOOTH_CHART', true),
        'track_changes' => env('DENTAL_TRACK_TOOTH_CHANGES', true),
    ],
    'marketplace' => [
        'enabled' => env('DENTAL_MARKETPLACE_ENABLED', true),
        'sync_interval_minutes' => env('DENTAL_SYNC_INTERVAL', 15),
    ],
    'fraud_detection' => [
        'enabled' => env('DENTAL_FRAUD_DETECTION_ENABLED', true),
        'threshold' => env('DENTAL_FRAUD_THRESHOLD', 0.7),
        'check_insurance' => env('DENTAL_CHECK_INSURANCE', true),
    ],
    'security' => [
        'compliance_152_fz' => env('DENTAL_COMPLIANCE_152_FZ', true),
        'encrypt_medical_data' => env('DENTAL_ENCRYPT_MEDICAL_DATA', true),
        'require_2fa_for_medical_data' => env('DENTAL_REQUIRE_2FA_MEDICAL', true),
    ],
];
```

## Integration with CatVRF Marketplace

### Synchronization
- **Procedures** — процедуры синхронизируются с маркетплейсом
- **Appointments** — приёмы из маркетплейса автоматически попадают в CRM
- **Prices** — цены обновляются автоматически
- **Availability** — доступность синхронизируется в реальном времени

### Webhooks
- Webhook URL настраивается в `config/crm-dental.php`
- Секретный ключ для валидации: `DENTAL_WEBHOOK_SECRET`

## Fraud Detection

### Features
- **Fraud Score** — оценка подозрительности клиента (0-1)
- **Patient History Check** — проверка истории пациентов
- **Insurance Check** — проверка страховых полисов

### Thresholds
- Порог блокировки: `DENTAL_FRAUD_THRESHOLD` (по умолчанию 0.7)

## Patient Management

### Patient Statuses
- **Active** — активен
- **Inactive** — неактивен
- **New** — новый
- **VIP** — VIP

### Medical History
- **Required** — обязательно
- **Auto Update** — автоматическое обновление
- **Retention Years** — 10 лет хранения

### Reminders
- **SMS** — SMS-напоминания
- **Email** — email-напоминания
- **Push** — push-уведомления

## Tooth Charts

### Numbering Systems
- **FDI** — Международная система (ISO 3950)
- **Universal** — Универсальная система (США)
- **Palmer** — Система Палмера

### Features
- **Auto Update** — автоматическое обновление зубной карты
- **Track Changes** — отслеживание истории изменений
- **Visual Representation** — визуальное представление

## Procedure Categories

### Diagnostics
- **Examination** — осмотр
- **X-Ray** — рентген
- **CT Scan** — КТ
- **MRI** — МРТ

### Preventive
- **Cleaning** — чистка
- **Fluoride Treatment** — фторирование
- **Sealants** — герметизация фиссур
- **Oral Hygiene Education** — обучение гигиене

### Restorative
- **Filling** — пломбирование
- **Inlay/Onlay** — вкладки/накладки
- **Crown** — коронка
- **Bridge** — мостовидный протез

### Endodontic
- **Root Canal** — лечение каналов
- **Apicoectomy** — апикоэктомия
- **Pulp Capping** — покрытие пульпы

### Periodontal
- **Scaling** — снятие зубного камня
- **Root Planing** — планирование корня
- **Gum Graft** — пересадка десны
- **Pocket Reduction** — уменьшение кармана

### Prosthetic
- **Dentures** — съёмные протезы
- **Implants** — имплантация
- **Veneers** — виниры
- **Bridges** — мостовидные протезы

### Orthodontic
- **Braces** — брекеты
- **Aligners** — элайнеры
- **Retainers** — ретейнеры
- **Expanders** — расширяющие аппараты

### Surgical
- **Extraction** — удаление
- **Wisdom Teeth** — удаление зубов мудрости
- **Bone Graft** — костная пластика
- **Sinus Lift** — синус-лифтинг

### Cosmetic
- **Teeth Whitening** — отбеливание
- **Bonding** — бондинг
- **Gingivectomy** — гингивэктомия
- **Smile Makeover** — улучшение улыбки

## Cache Configuration

### TTL Settings
- **Procedures**: 3600 секунд (1 час)
- **Appointments**: 300 секунд (5 минут)
- **Inventory**: 180 секунд (3 минуты)
- **Prices**: 600 секунд (10 минут)

### Cache Tags
- `dental:procedures`
- `dental:appointments`
- `dental:inventory`

## Queue Configuration

- **appointment_created**: `dental`
- **appointment_confirmed**: `dental`
- **sync_marketplace**: `dental-sync`
- **notifications**: `dental-notifications`
- **fraud_check**: `dental-fraud`

## Automations

### Notifications
- **appointment_created** — уведомление о создании приёма
- **appointment_confirmed** — подтверждение приёма
- **appointment_reminder** — напоминание за 24 часа до приёма
- **treatment_reminder** — напоминание за 7 дней до лечения
- **follow_up** — последующее наблюдение через 3 дня
- **recall** — отзыв через 6 месяцев

### Auto Status Changes
- **confirm_appointments** — автоматическое подтверждение приёмов
- **complete_treatments** — автоматическое завершение лечения
- **update_tooth_charts** — автоматическое обновление зубных карт

### Treatment Recommendations
- **Based on History** — рекомендации на основе истории
- **Based on Age** — рекомендации на основе возраста
- **Preventive Care** — профилактический уход

## Analytics

### Metrics
- **conversion_rate** — конверсия
- **average_treatment_value** — средняя стоимость лечения
- **patient_retention** — удержание пациентов
- **treatment_frequency** — частота лечения
- **revenue_per_patient** — выручка на пациента

### Retention
- Хранение аналитики: 365 дней (настраивается через `DENTAL_ANALYTICS_RETENTION`)

## Security & Compliance

### Compliance
- **152-FZ Compliance** — соответствие закону 152-ФЗ
- **Medical Data Encryption** — шифрование медицинских данных
- **2FA for Medical Data** — двухфакторная аутентификация для медицинских данных

### Security Features
- **Max Appointments Per Day** — ограничение на количество приёмов за день
- **Max Amount Without Verification** — ограничение на сумму без доп. проверки

### Thresholds
- Максимальное количество приёмов в день: 50
- Максимальная сумма без верификации: 100,000 ₽

## Integrations

### CRM Systems
- **AmoCRM** — интеграция через API
- **Bitrix24** — интеграция через API

### Medical Equipment
- **X-Ray** — рентгеновское оборудование
- **Intraoral Camera** — внутриротовая камера
- **CAD/CAM** — CAD/CAM системы

### Insurance
- **DMS** — ДМС
- **VHI** — ОМС

## Usage

### Activation
```php
// .env
DENTAL_ENABLED=true
DENTAL_MARKETPLACE_ENABLED=true
DENTAL_FRAUD_DETECTION_ENABLED=true
DENTAL_COMPLIANCE_152_FZ=true
```

### Creating a Treatment Plan
```php
use Modules\Dental\Application\Services\TreatmentPlanService;

$treatmentPlanService = app(TreatmentPlanService::class);

$plan = $treatmentPlanService->createTreatmentPlan([
    'patient_id' => $patientId,
    'doctor_id' => $doctorId,
    'procedures' => [
        ['procedure_id' => $procedureId1, 'quantity' => 1],
        ['procedure_id' => $procedureId2, 'quantity' => 2],
    ],
    'start_date' => now(),
    'estimated_completion_date' => now()->addDays(30),
]);
```

### Managing Tooth Charts
```php
use Modules\Dental\Application\Services\ToothChartService;

$toothChartService = app(ToothChartService::class);

// Обновление зубной карты
$toothChartService->updateTooth($toothChartId, $toothNumber, [
    'status' => 'treated',
    'procedure_id' => $procedureId,
    'notes' => 'Пломбирование',
]);
```

## Acceptance Criteria

- [ ] Модуль полностью адаптирован под стоматологию (процедуры, планы лечения, зубные карты)
- [ ] Реал-тайм дашборд приёмов
- [ ] Автоматические воронки и уведомления работают без ручных действий
- [ ] Глубокая интеграция с CatVRF (платежи, отзывы, fraud-проверка)
- [ ] Соответствие 152-ФЗ (шифрование медицинских данных)
- [ ] Удобный интерфейс для врачей и пациентов
- [ ] Покрытие тестами ≥ 95%
- [ ] Конфигурационный файл создан и документирован
- [ ] README с инструкциями по активации и использованию

## Testing

### Test Coverage
- Unit тесты для сервисов (TreatmentPlanService, ProcedureService, ToothChartService)
- Feature тесты для Filament ресурсов
- Интеграционные тесты для маркетплейса
- Тесты Fraud Detection
- Тесты шифрования медицинских данных

### Running Tests
```bash
php artisan test --filter=Dental
```

## Documentation

- **Configuration**: `config/crm-dental.php`
- **Module Directory**: `modules/Dental/`
- **This README**: `docs/DENTAL_MODULE_IMPLEMENTATION_SUMMARY.md`

## Support

Для вопросов и поддержки обращайтесь к документации проекта или создайте issue в репозитории.
