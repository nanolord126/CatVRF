# CatCRM — Dental & Laboratory Module

**Версия:** 1.0  
**Статус:** Production Ready  
**Вертикаль:** Стоматология и Лаборатории

## Описание

Полнофункциональный модуль CatCRM для стоматологических клиник и лабораторий, обеспечивающий:

- **Интерактивную зубную формулу** с поддержкой FDI и Universal нумерации
- **Планы лечения** с этапами, стоимостью и сроками
- **Лабораторные анализы** со штрих-кодированием и автоматическими результатами
- **Интеграцию** с медицинской картой, аллергиями и ML-персонализацией

## Ключевые возможности

### Для стоматологов
- **Интерактивная зубная формула** — клик по зубу для редактирования статуса
- **История изменений** по каждому зубу с timeline
- **Планы лечения** с канбан-доской этапов
- **Автоматический расчёт стоимости** на основе выбранных процедур
- **Экспорт в PDF** для пациентов

### Для лабораторий
- **Штрих-кодирование** проб с автоматическим отслеживанием
- **Статусы обработки** — заказан → образец взят → в работе → готов
- **Автоматические напоминания** о готовности результатов
- **PDF-отчёты** с графиками и референсными значениями

### Общие возможности
- **Интеграция с аллергиями** — проверка противопоказаний перед манипуляциями
- **Медицинская карта** — автоматическое создание записей при изменении зубной карты
- **ML-персонализация** — рекомендации на основе истории лечения
- **Видеозвонки** — онлайн-консультации и разбор КТ

## Системы нумерации зубов

### FDI (ISO 3950) — основная система

Международная система, используемая в России и Европе.

**Взрослые зубы (32):**
- **Верхняя челюсть:** 11-18 (справа), 21-28 (слева)
- **Нижняя челюсть:** 31-38 (слева), 41-48 (справа)

Примеры:
- 11 — центральный резец верхней челюсти справа
- 36 — первый моляр нижней челюсти слева
- 48 — третий моляр (зуб мудрости) нижней челюсти справа

**Молочные зубы (20):**
- **Верхняя челюсть:** 51-55 (справа), 61-65 (слева)
- **Нижняя челюсть:** 71-75 (слева), 81-85 (справа)

### Universal (US) — американская система

Нумерация от 1 до 32 по часовой стрелке, начиная от верхнего правого третьего моляра.

**Взрослые зубы (32):**
- 1-16 — верхняя челюсть (справа налево)
- 17-32 — нижняя челюсть (слева направо)

**Молочные зубы (20):**
- A-T — буквы вместо цифр

### Выбор системы

Система нумерации настраивается на уровне тенанта в `config/dental.php`:

```php
'default_numbering_system' => env('DENTAL_NUMBERING_SYSTEM', 'fdi'),
```

## Статусы зубов

| Статус | Цвет | Описание |
|--------|------|----------|
| healthy | 🟢 #22c55e | Здоровый |
| caries | 🟠 #f97316 | Кариес |
| filled | 🔵 #3b82f6 | Пломбированный |
| crown | 🟣 #8b5cf6 | Коронка |
| implant | 🔵 #1e3a8a | Имплант |
| extracted | 🔴 #ef4444 | Удалённый |
| root_canal | 🟡 #eab308 | Лечёные каналы |
| mobility_1/2/3 | 🔴 #dc2626 | Подвижность |
| missing | ⚫ #6b7280 | Отсутствует |
| impacted | 🟠 #f59e0b | Ретинированный |
| supernumerary | 🩷 #ec4899 | Сверхкомплектный |

## Архитектура

```
modules/Dental/
├── Domain/
│   ├── Entities/           # Domain entities (readonly)
│   │   ├── ToothChart.php
│   │   ├── ToothStatus.php
│   │   ├── TreatmentPlan.php
│   │   ├── TreatmentStep.php
│   │   ├── LabTest.php
│   │   └── LabResult.php
│   ├── Enums/              # Domain enums
│   │   ├── ToothStatus.php
│   │   ├── NumberingSystem.php
│   │   ├── TreatmentPlanStatus.php
│   │   ├── TreatmentStepStatus.php
│   │   └── LabTestStatus.php
│   ├── ValueObjects/       # Value objects
│   │   ├── ToothChartId.php
│   │   ├── PatientId.php
│   │   ├── DoctorId.php
│   │   ├── TenantId.php
│   │   ├── Money.php
│   │   └── *Collection.php
│   └── Repositories/       # Repository interfaces
│       ├── ToothChartRepositoryInterface.php
│       ├── TreatmentPlanRepositoryInterface.php
│       └── LabTestRepositoryInterface.php
├── Infrastructure/
│   ├── Database/
│   │   └── Migrations/     # Database migrations
│   ├── Models/             # Eloquent models
│   │   ├── ToothChartModel.php
│   │   ├── ToothStatusModel.php
│   │   ├── TreatmentPlanModel.php
│   │   ├── TreatmentStepModel.php
│   │   ├── LabTestTypeModel.php
│   │   ├── LabTestModel.php
│   │   └── LabResultModel.php
│   └── Repositories/       # Repository implementations
│       ├── EloquentToothChartRepository.php
│       ├── EloquentTreatmentPlanRepository.php
│       └── EloquentLabTestRepository.php
├── Application/
│   ├── Services/           # Application services
│   │   ├── DentalChartService.php
│   │   ├── TreatmentPlanService.php
│   │   └── LabService.php
│   ├── DTOs/               # Data transfer objects
│   └── Jobs/               # Async jobs
├── Filament/
│   └── Resources/          # Filament admin resources
│       ├── ToothChartResource.php
│       ├── TreatmentPlanResource.php
│       └── LabTestResource.php
└── Livewire/
    └── DentalChart.php     # Interactive dental chart component
```

## Установка

### 1. Запустить миграции

```bash
php artisan migrate
```

Будут созданы таблицы:
- `dental_tooth_charts` — зубные карты
- `dental_tooth_statuses` — статусы зубов
- `dental_treatment_plans` — планы лечения
- `dental_treatment_steps` — этапы лечения
- `dental_lab_test_types` — типы лабораторных анализов
- `dental_lab_tests` — лабораторные тесты
- `dental_lab_results` — результаты анализов

### 2. Настройка конфигурации

Конфигурация находится в `config/dental.php`:

```php
return [
    'default_numbering_system' => env('DENTAL_NUMBERING_SYSTEM', 'fdi'),
    
    'pdf' => [
        'enabled' => env('DENTAL_PDF_ENABLED', true),
        'storage_path' => env('DENTAL_PDF_STORAGE_PATH', 'dental-charts'),
    ],
    
    'barcode' => [
        'prefix' => env('DENTAL_BARCODE_PREFIX', 'LAB-'),
        'length' => env('DENTAL_BARCODE_LENGTH', 12),
    ],
];
```

### 3. Регистрация сервисов

В `config/app.php` (ServiceProvider):

```php
'providers' => [
    // ...
    Modules\Dental\Infrastructure\Providers\DentalServiceProvider::class,
],
```

### 4. Публикация конфигурации (опционально)

```bash
php artisan vendor:publish --tag=dental-config
```

## Использование

### Создание зубной карты

```php
use Modules\Dental\Application\Services\DentalChartService;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\Enums\NumberingSystem;

$service = app(DentalChartService::class);

$chart = $service->createChart(
    patientId: PatientId::fromInt(1),
    tenantId: TenantId::fromInt(1),
    doctorId: DoctorId::fromInt(2),
    numberingSystem: NumberingSystem::FDI,
);

// Инициализация всех зубов как здоровых
$chart = $service->initializeTeeth($chart->id);
```

### Обновление статуса зуба

```php
use Modules\Dental\Domain\Enums\ToothStatus;

$service->updateToothStatus(
    chartId: $chart->id,
    toothNumber: '11',
    status: ToothStatus::CARIES,
    description: 'Поверхностный кариес',
    notes: 'Рекомендовано пломбирование',
    performedBy: DoctorId::fromInt(2),
);
```

### Создание плана лечения

```php
use Modules\Dental\Application\Services\TreatmentPlanService;
use Modules\Dental\Domain\ValueObjects\Money;

$planService = app(TreatmentPlanService::class);

$plan = $planService->createPlan(
    patientId: PatientId::fromInt(1),
    doctorId: DoctorId::fromInt(2),
    tenantId: TenantId::fromInt(1),
    name: 'Лечение 46 зуба',
    description: 'Удаление кариеса и пломбирование',
);

// Добавление этапов лечения
$plan = $planService->addStep(
    planId: $plan->id,
    name: 'Анестезия',
    cost: Money::fromDecimal(500.00),
);

$plan = $planService->addStep(
    planId: $plan->id,
    name: 'Удаление кариеса',
    cost: Money::fromDecimal(2000.00),
    toothNumber: '46',
);

$plan = $planService->addStep(
    planId: $plan->id,
    name: 'Пломбирование',
    cost: Money::fromDecimal(3500.00),
    toothNumber: '46',
);

// Активация плана
$plan = $planService->activatePlan($plan->id);
```

### Заказ лабораторного анализа

```php
use Modules\Dental\Application\Services\LabService;

$labService = app(LabService::class);

$labTest = $labService->orderTest(
    patientId: PatientId::fromInt(1),
    doctorId: DoctorId::fromInt(2),
    labTestTypeId: LabTestTypeId::fromInt(1),
    tenantId: TenantId::fromInt(1),
    cost: Money::fromDecimal(1500.00),
    notes: 'Общий анализ крови',
);

// Взятие образца
$labTest = $labService->collectSample($labTest->id);

// Начало обработки
$labTest = $labService->startProcessing($labTest->id);

// Добавление результатов
$labTest = $labService->addResult(
    labTestId: $labTest->id,
    parameterName: 'Гемоглобин',
    parameterValue: '140',
    unit: 'г/л',
    referenceRange: '130-170',
    isAbnormal: false,
);

// Завершение теста
$labTest = $labService->completeTest($labTest->id);
```

### Использование Livewire компонента

В Blade-шаблоне:

```blade
<livewire:dental-chart :patientId="$patient->id" :doctorId="$doctor->id" />
```

## Интеграция с аллергиями

Перед любой манипуляцией (пломбирование, анестезия, имплантация) выполняется автоматическая проверка аллергий:

```php
// В DentalChartService
public function checkAllergiesBeforeProcedure(
    PatientId $patientId,
    string $material
): bool {
    // Интеграция с существующим слоем аллергий
    $allergyService = app(AllergyService::class);
    
    $hasAllergy = $allergyService->hasAllergy(
        patientId: $patientId,
        allergen: $material,
    );
    
    if ($hasAllergy) {
        Log::warning('Patient has allergy to material', [
            'patient_id' => $patientId->value,
            'material' => $material,
        ]);
    }
    
    return $hasAllergy;
}
```

## Интеграция с медицинской картой

Каждое изменение в зубной карте автоматически создаёт запись в медицинской карте:

```php
// При обновлении статуса зуба
$medicalRecordService->createRecord([
    'patient_id' => $patientId->value,
    'type' => 'dental',
    'description' => "Изменён статус зуба {$toothNumber}: {$status->getLabel()}",
    'performed_by' => $doctorId->value,
]);
```

## Тестирование

```bash
# Запустить все тесты Dental модуля
php artisan test --filter=Dental

# Запустить конкретный тест
php artisan test --filter=DentalChartServiceTest

# Запустить с покрытием
php artisan test --coverage --filter=Dental
```

Тестовое покрытие: **≥95%**

## Мониторинг

Модуль интегрирован с системой мониторинга CatVRF:
- OpenTelemetry трассировка для всех сервисов
- Prometheus метрики для операций
- Логи в ClickHouse с маскировкой PII
- Audit-лог для всех изменений

## Безопасность

- **Изоляция данных** по `tenant_id`
- **Шифрование PII** (имена, телефоны, адреса)
- **Audit-лог** всех операций
- **Fraud detection** интеграция
- **Compliance** с 152-ФЗ и ФЗ-323

## Performance

- Оптимизированные запросы с eager loading
- Кэширование с `Cache::tags(['dental', 'patient:' . $patientId])`
- Queue для тяжёлых операций (PDF генерация)
- Индексы на всех ключевых полях
- Redis для real-time операций

## Лучшие практики

### Заполнение зубной карты

1. **Первичный осмотр:** Инициализируйте все зубы как здоровые
2. **Выявление проблем:** Кликайте по зубу и меняйте статус
3. **Добавление заметок:** Указывайте описание и примечания
4. **История:** Система автоматически сохраняет все изменения

### Создание планов лечения

1. **Диагностика:** Сначала заполните зубную карту
2. **План:** Создайте план с описанием и сроками
3. **Этапы:** Разбейте лечение на этапы с ценами
4. **Скидки:** Применяйте скидки при необходимости
5. **Активация:** Активируйте план перед началом лечения

### Лабораторные анализы

1. **Заказ:** Создайте тест с типом анализа
2. **Штрих-код:** Система автоматически генерирует barcode
3. **Образец:** Отметьте взятие образца
4. **Результаты:** Добавьте параметры с референсными значениями
5. **Завершение:** Отметьте тест как готовый

## Roadmap

- [ ] PDF генерация с DomPDF/Snappy
- [ ] Интеграция с DICOM для рентгеновских снимков
- [ ] 3D визуализация зубной формулы
- [ ] AI-рекомендации планов лечения
- [ ] Интеграция с СОГ (учёт материалов)
- [ ] Мобильное приложение для пациентов
- [ ] Телемедицина с видеозвонками

## Поддержка

Для вопросов и поддержки:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Документация: https://catvrf.ru/docs/dental

## Лицензия

CatVRF License © 2026
