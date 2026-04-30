# CatCRM Vet & Grooming Module

**Версия:** 1.0  
**Дата:** 23.04.2026  
**Модуль:** Veterinary Clinics & Grooming Salons CRM

## Обзор

Модуль CatCRM Vet & Grooming — это мощная система управления ветеринарными клиниками и груминг-салонами, построенная по принципам Clean Architecture и DDD. Модуль обеспечивает:

- **Электронную медицинскую карту питомца** с полной историей визитов, диагнозов, аллергий и хронических заболеваний
- **Вакцинационный график** в соответствии с рекомендациями WSAVA 2024 и законодательством РФ
- **Интеграцию ветеринарии и груминга** — автоматическая подтяжка аллергий и медицинских замечаний для грумеров
- **Фото-отчёты** до/после для маркетинга груминг-услуг
- **Напоминания** о вакцинации и повторном груминге
- **Экзотический груминг** — сертификация грумеров, протоколы безопасности, автоматическая блокировка несанкционированных процедур
- **Полное соответствие** 152-ФЗ, ФЗ-323 и приказам Минсельхоза

## Архитектура

```
modules/VetGrooming/
├── Domain/
│   ├── Entities/           # Доменные сущности (readonly)
│   ├── Enums/              # Перечисления (VaccineType, ConditionType, etc.)
│   ├── Repositories/       # Интерфейсы репозиториев
│   ├── DTOs/               # Data Transfer Objects
│   ├── Events/             # Доменные события
│   └── ValueObjects/       # Value Objects
├── Infrastructure/
│   ├── Models/             # Eloquent модели
│   ├── Repositories/       # Реализации репозиториев
│   └── Providers/          # Service Providers
├── Application/
│   ├── Services/           # Сервисы приложения
│   ├── Jobs/               # Фоновые задачи
│   └── DTOs/               # Application DTOs
├── Filament/
│   └── Resources/          # Filament admin ресурсы
└── Presentation/
    └── Livewire/           # Livewire компоненты
```

## Установка

### 1. Запуск миграций

```bash
php artisan migrate
```

Миграция создаёт следующие таблицы:
- `pet_vaccinations` — детальные записи вакцинаций
- `pet_chronic_conditions` — хронические заболевания и аллергии
- `pet_weight_history` — история веса
- `grooming_sessions` — сеансы груминга с фото
- `pet_medical_documents` — медицинские документы (зашифрованные)

### 2. Конфигурация

Добавьте в `.env`:

```env
# Vaccination Settings
VET_WSAVA_2024_COMPLIANT=true
VET_VACCINATION_REMINDERS_ENABLED=true

# Medical Records Encryption (152-ФЗ compliance)
VET_MEDICAL_ENCRYPTION_ENABLED=true
VET_MEDICAL_ENCRYPTION_KEY=your-encryption-key
VET_MEDICAL_STORAGE_DISK=s3
VET_MEDICAL_MAX_FILE_SIZE_MB=50

# Grooming Photos
GROOMING_PHOTO_STORAGE_DISK=s3
GROOMING_PHOTO_AUTO_COMPRESS=true

# Integration
VET_PAYMENT_INTEGRATION_ENABLED=true
VET_LOYALTY_INTEGRATION_ENABLED=true
VET_SMS_ENABLED=false
VET_EMAIL_ENABLED=true
VET_TELEGRAM_ENABLED=false

# Compliance
VET_AUDIT_LOG_ENABLED=true
```

### 3. Регистрация модуля

Добавьте провайдер в `config/app.php`:

```php
'providers' => [
    // ...
    Modules\VetGrooming\Infrastructure\VetGroomingServiceProvider::class,
],
```

### 4. Настройка планировщика напоминаний

В `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Ежедневная проверка просроченных вакцинаций
    $schedule->job(new \Modules\VetGrooming\Application\Jobs\CheckVaccinationRemindersJob())
        ->dailyAt('09:00');
}
```

## WSAVA 2024: Рекомендации по вакцинации

### Собаки (Dogs)

#### Основные (Core) вакцины

| Вакцина | Возраст первой дозы | Интервал между дозами | Ревакцинация | Обязательность |
|---------|---------------------|----------------------|--------------|---------------|
| DHP (чумка, парвовирус, аденовирус) | 6-8 недель | 3-4 недели | Каждые 3 года* | Рекомендуется |
| Бешенство (Rabies) | 12-16 недель | - | Ежегодно | **Обязательно по закону РФ** |
| Лептоспироз | 8-12 недель | 3-4 недели | Ежегодно | Регионально |
| Боррелиоз (клещевой) | 12 недель | 3-4 недели | Ежегодно | Регионально |

\* После первой ревакцинации в 12 месяцев

#### Схема для щенков

- **6-8 недель:** DHP (первая доза)
- **10-12 недель:** DHP (вторая доза) + Лептоспироз (если эндемичный регион)
- **14-16 недель:** DHP (третья доза) + Бешенство
- **12 месяцев (или 6 месяцев):** Ревакцинация DHP + Бешенство
- **Далее:** DHP — каждые 3 года, Бешенство — ежегодно

### Кошки (Cats)

#### Основные (Core) вакцины

| Вакцина | Возраст первой дозы | Интервал между дозами | Ревакцинация | Обязательность |
|---------|---------------------|----------------------|--------------|---------------|
| FPV (панлейкопения) | 8-9 недель | 3-4 недели | Каждые 3 года* | Рекомендуется |
| FCV + FHV-1 (калицивирус + герпес) | 8-9 недель | 3-4 недели | Каждые 3 года* | Рекомендуется |
| Бешенство (Rabies) | 12-16 недель | - | Ежегодно | **Обязательно по закону РФ** |
| FeLV (лейкоз) | 8-12 недель | 3-4 недели | Ежегодно | Для кошек с риском |

\* После первой ревакцинации в 12 месяцев

#### Схема для котят

- **8-9 недель:** FPV + FCV/FHV-1 (первая доза)
- **11-12 недель:** FPV + FCV/FHV-1 (вторая доза) + Бешенство
- **15-16 недель:** Третья доза (при необходимости)
- **12 месяцев (или 6 месяцев):** Ревакцинация всех вакцин
- **Далее:** Основные — каждые 3 года, Бешенство — ежегодно

### Ключевые принципы WSAVA 2024

1. **Не вакцинируйте чаще, чем необходимо** — большинство основных вакцин эффективны 3+ лет после первой ревакцинации
2. **Титрование антител** — для взрослых животных можно проверить иммунитет вместо автоматической ревакцинации
3. **Индивидуальный подход** — учитывайте образ жизни, региональные риски, возраст и здоровье
4. **Бешенство** — остаётся обязательной ежегодной вакциной во многих странах, включая РФ

## Использование сервисов

### VaccinationScheduleService

```php
use Modules\VetGrooming\Application\Services\VaccinationScheduleService;
use Carbon\CarbonImmutable;

$service = app(VaccinationScheduleService::class);

// Генерация графика для щенка 8 недель
$schedule = $service->generateSchedule(
    petId: 1,
    species: 'dog',
    birthDate: CarbonImmutable::now()->subWeeks(8),
    riskFactors: ['endemic_lepto', 'tick_borne_risk']
);

// Получить ближайшие прививки
$due = $service->getNextDueVaccinations(petId: 1);

// Отметить вакцинацию как выполненную
$completed = $service->markAsCompleted(
    vaccinationId: 1,
    actualDate: CarbonImmutable::now(),
    notes: 'Реакций не было'
);

// Проверка аллергий перед вакцинацией
$warnings = $service->checkMedicationAllergies(petId: 1, ['Nobivac DHPPi']);
```

### MedicalRecordService

```php
use Modules\VetGrooming\Application\Services\MedicalRecordService;
use Modules\VetGrooming\Domain\Enums\ConditionType;

$service = app(MedicalRecordService::class);

// Создать запись об аллергии
$condition = $service->createCondition(
    tenantId: 1,
    petId: 1,
    conditionType: ConditionType::ALLERGY_MEDICATION,
    conditionName: 'Пенициллин',
    severity: 'severe',
    triggers: ['амоксициллин', 'ампициллин']
);

// Получить краткую сводку для врача (30-секундный просмотр)
$summary = $service->getQuickSummary(petId: 1);
// Возвращает: аллергии, критические состояния, непереносимость анестезии

// Получить информацию для грумера
$groomingInfo = $service->getGroomingMedicalInfo(petId: 1);
// Возвращает: аллергии, кожные заболевания, требует ли особого обращения

// Прикрепить медицинский документ
$document = $service->attachDocument(
    tenantId: 1,
    petId: 1,
    documentType: MedicalDocumentType::LAB_RESULT,
    filePath: 'storage/medical/lab_result.pdf',
    fileName: 'Анализ крови.pdf',
    uploadedBy: 1,
    isEncrypted: true
);
```

### GroomingService

```php
use Modules\VetGrooming\Application\Services\GroomingService;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;

$service = app(GroomingService::class);

// Создать сеанс груминга
$session = $service->createSession(
    tenantId: 1,
    petId: 1,
    serviceType: GroomingServiceType::FULL_GROOMING,
    groomerId: 5,
    appointmentId: 10
);
// Аллергии автоматически подтянутся из медицинской карты

// Начать сеанс
$session = $service->startSession(sessionId: $session->id, groomerId: 5);

// Добавить фото "до"
$session = $service->addBeforePhotos(
    sessionId: $session->id,
    photos: ['storage/grooming/before_1.jpg', 'storage/grooming/before_2.jpg']
);

// Завершить сеанс с фото "после"
$session = $service->completeSession(
    sessionId: $session->id,
    behaviorRating: BehaviorRating::GOOD,
    behaviorNotes: 'Спокойный, хорошо переносит процедуры',
    productsUsed: ['Шампунь Hypoallergenic', 'Кондиционер'],
    afterPhotos: ['storage/grooming/after_1.jpg', 'storage/grooming/after_2.jpg'],
    medicalNotes: 'Кожа в норме, раздражений нет'
);

// Получить историю с фото для маркетинга
$photoReport = $service->generatePhotoReport(petId: 1);
```

## Безопасность и Compliance

### Шифрование медицинских данных

Все медицинские данные шифруются при хранении в соответствии с 152-ФЗ:

```php
// В модели
protected $casts = [
    'diagnosis' => 'encrypted',
    'treatment' => 'encrypted',
    'medications' => 'encrypted',
];
```

### Аудит лог

Все действия с медицинской картой логируются:

- Доступ к карте (кто, когда)
- Изменения диагнозов и назначений
- Загрузка и скачивание документов
- Вакцинации и реакции

### Соответствие законодательству РФ

- **Бешенство:** Обязательная ежегодная вакцинация
- **Хранение медицинских данных:** 10 лет
- **Шифрование:** Обязательно для медицинских данных (152-ФЗ)
- **Аудит:** Полный лог всех действий

## Интеграции

### Платежи

```php
// Автоматическое создание счёта после вакцинации
// Интеграция с модулем платежей CatVRF
```

### Лояльность

```php
// Начисление баллов за вакцинацию и груминг
// Настройка в config/crm-vet-grooming.php
'loyalty' => [
    'points_per_vaccination' => 100,
    'points_per_grooming' => 50,
],
```

### Уведомления

Модуль поддерживает множественные каналы уведомлений:
- Push-уведомления
- SMS
- Email
- Telegram

## API Endpoints

### Вакцинации

```
GET  /api/vet/pets/{id}/vaccinations
POST /api/vet/pets/{id}/vaccinations
PUT  /api/vet/vaccinations/{id}
GET  /api/vet/pets/{id}/vaccinations/schedule
POST /api/vet/vaccinations/{id}/complete
```

### Медицинские записи

```
GET  /api/vet/pets/{id}/medical-summary
POST /api/vet/pets/{id}/conditions
PUT  /api/vet/conditions/{id}
GET  /api/vet/pets/{id}/documents
POST /api/vet/pets/{id}/documents
```

### Груминг

```
GET  /api/grooming/pets/{id}/sessions
POST /api/grooming/sessions
PUT  /api/grooming/sessions/{id}/start
PUT  /api/grooming/sessions/{id}/complete
POST /api/grooming/sessions/{id}/photos
GET  /api/grooming/groomers/{id}/schedule/{date}
```

## Тестирование

```bash
# Запуск тестов модуля
./vendor/bin/pest tests/Modules/VetGrooming

# С покрытием
./vendor/bin/pest --coverage
```

## Требования к покрытию тестами

- **≥95%** покрытие кода тестами
- Unit тесты для всех сервисов
- Feature тесты для API endpoints
- Integration тесты для репозиториев

## Производительность

- **Кэширование:** Использовать Cache::tags для инвалидации
- **Индексы:** Все составные индексы созданы в миграциях
- **N+1:** Eager loading для всех отношений
- **Очереди:** Все тяжёлые операции в очередях

## Мониторинг

Метрики для Prometheus:
- `vet_vaccinations_overdue_total` — количество просроченных вакцинаций
- `vet_grooming_sessions_duration_seconds` — длительность сеансов груминга
- `vet_medical_documents_uploaded_total` — количество загруженных документов
- `vet_reminders_sent_total` — количество отправленных напоминаний

## Поддержка

Для вопросов и проблем:
- GitHub Issues: https://github.com/nanolord126/CatVRF/issues
- Документация: `/docs/vet-grooming/`

## Лицензия

Proprietary — часть проекта CatVRF
