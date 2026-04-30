# Beauty Certification System

## Overview

The Beauty Certification System is a comprehensive certification and continuous professional development platform for beauty specialists across four verticals: General Beauty, Makeup, Brow, and Lash. The system ensures client safety, enables salon owners to monitor staff qualification levels, automatically tracks certificate expiration dates, and motivates specialists through internal and external certification programs.

## Architecture

The system follows **Clean Architecture** principles with clear separation of concerns:

```
modules/BeautyMasters/
├── Domain/
│   ├── Entities/           # Business entities (readonly)
│   ├── Events/             # Domain events
│   └── Repositories/       # Repository interfaces
├── Infrastructure/
│   ├── Models/             # Eloquent models
│   └── Repositories/       # Repository implementations
├── Application/
│   ├── Services/           # Application services
│   └── Jobs/               # Background jobs
└── Filament/
    ├── Resources/          # Admin resources
    └── Widgets/            # Dashboard widgets
```

## Supported Verticals

### 1. Beauty (General)
- **Specializations**: Маникюр, Педикюр, Косметология, Визаж
- **Certification Levels**: Junior, Certified, Advanced, Master, Expert

### 2. Makeup
- **Specializations**: Свадебный макияж, Вечерний макияж, Airbrush, Мужской грим, Editorial
- **Certification Levels**: Junior, Certified, Advanced, Master, Expert

### 3. Brow
- **Specializations**: Powder Brows, Microblading, Henna, Lamination, Male Brow
- **Certification Levels**: Junior, Certified, Advanced, Master, Expert

### 4. Lash
- **Specializations**: Classic, Volume 2D-3D, Mega Volume, Lash Lifting, Hybrid
- **Certification Levels**: Junior, Certified, Advanced, Master, Expert

## Database Schema

### Tables

#### Certifications
- `beauty_certifications`
- `makeup_certifications`
- `brow_certifications`
- `lash_certifications`

**Fields:**
- `master_id` (FK to beauty_masters)
- `certification_type` (enum: internal/external)
- `name` (string)
- `issuer` (string, nullable)
- `issue_date` (date)
- `expiry_date` (date, nullable)
- `certificate_number` (string, nullable)
- `document_file` (string, nullable)
- `status` (enum: active/expired/pending_verification/revoked/suspended)
- `notes` (text, nullable)

#### Specializations
- `beauty_specializations`
- `makeup_specializations`
- `brow_specializations`
- `lash_specializations`

**Fields:**
- `master_id` (FK to beauty_masters)
- `specialization` (string)
- `level` (enum: basic/advanced/expert)
- `notes` (text, nullable)

#### Development Plans
- `beauty_development_plans`
- `makeup_development_plans`
- `brow_development_plans`
- `lash_development_plans`

**Fields:**
- `master_id` (FK to beauty_masters, unique)
- `required_courses` (JSON)
- `progress_percent` (integer)
- `next_certification_date` (date, nullable)
- `mentor_notes` (text, nullable)

#### Test Results
- `certification_test_results` (shared across all verticals)

**Fields:**
- `master_id` (FK to beauty_masters)
- `test_name` (string)
- `vertical` (enum: beauty/makeup/brow/lash)
- `theory_score` (integer)
- `practice_score` (integer)
- `total_score` (integer)
- `passed` (boolean)
- `awarded_level` (enum: junior/certified/advanced/master/expert, nullable)
- `practical_work_photos` (JSON, nullable)
- `feedback` (text, nullable)
- `completed_at` (datetime)

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

The following migrations will be executed:
- `2026_04_23_000020_create_beauty_certifications_table.php`
- `2026_04_23_000021_create_beauty_specializations_table.php`
- `2026_04_23_000022_create_beauty_development_plans_table.php`
- `2026_04_23_000023_create_certification_test_results_table.php`
- `2026_04_23_000024_create_makeup_certifications_table.php`
- `2026_04_23_000025_create_makeup_specializations_table.php`
- `2026_04_23_000026_create_makeup_development_plans_table.php`
- `2026_04_23_000027_create_brow_certifications_table.php`
- `2026_04_23_000028_create_brow_specializations_table.php`
- `2026_04_23_000029_create_brow_development_plans_table.php`
- `2026_04_23_000030_create_lash_certifications_table.php`
- `2026_04_23_000031_create_lash_specializations_table.php`
- `2026_04_23_000032_create_lash_development_plans_table.php`

### 2. Register Service Providers (if needed)

Add the following to your `config/app.php`:

```php
'providers' => [
    // ...
    Modules\BeautyMasters\BeautyMastersServiceProvider::class,
],
```

### 3. Schedule the Expiration Check Job

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new \Modules\BeautyMasters\Application\Jobs\CheckExpiringCertificationsJob)
        ->daily()
        ->at('00:00')
        ->onQueue('certifications');
}
```

## Usage

### Creating a Certification

```php
use Modules\BeautyMasters\Application\Services\BeautyCertificationService;
use Modules\BeautyMasters\Domain\Entities\CertificationLevel;

$service = app(BeautyCertificationService::class);

$certification = $service->createInternalCertification(
    masterId: $masterId,
    name: 'Медицинский маникюр',
    expiryDate: new \DateTimeImmutable('+1 year'),
    awardedLevel: CertificationLevel::CERTIFIED
);
```

### Assigning a Specialization

```php
use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;

$specialization = $service->assignSpecialization(
    masterId: $masterId,
    specialization: 'Маникюр',
    level: SpecializationLevel::ADVANCED
);
```

### Verifying Certification

```php
$isCertified = $service->verifyCertification($masterId, 'Маникюр');
// Returns: true/false
```

### Recording Test Results

```php
$result = $service->recordTestResult(
    masterId: $masterId,
    testName: 'Beauty Certification Test',
    theoryScore: 85,
    practiceScore: 90,
    practicalWorkPhotos: ['photo1.jpg', 'photo2.jpg'],
    feedback: 'Excellent performance'
);
```

### Generating Development Plan

```php
$plan = $service->generateDevelopmentPlan($masterId);
```

### Blocking Master for Service

```php
$service->blockMasterForService($masterId, 'Маникюр');
// Triggers MasterBlockedDueToExpiredCertification event
```

## Events

The system dispatches the following domain events:

### CertificationExpiringSoon
Triggered when a certificate is expiring within 60 days.

```php
use Modules\BeautyMasters\Domain\Events\CertificationExpiringSoon;

Event::listen(CertificationExpiringSoon::class, function ($event) {
    // Send notification to master
    // Log warning
});
```

### CertificationExpired
Triggered when a certificate has expired.

```php
use Modules\BeautyMasters\Domain\Events\CertificationExpired;

Event::listen(CertificationExpired::class, function ($event) {
    // Update master status
    // Block service slots
    // Send notification
});
```

### MasterBlockedDueToExpiredCertification
Triggered when a master is blocked from providing a service due to expired certification.

```php
use Modules\BeautyMasters\Domain\Events\MasterBlockedDueToExpiredCertification;

Event::listen(MasterBlockedDueToExpiredCertification::class, function ($event) {
    // Remove master from schedule
    // Notify salon owner
});
```

## Filament Admin Panel

### Certification Dashboard
Navigate to: **Beauty Masters > Certification Dashboard**

Features:
- Overview statistics for all verticals
- Expiring certifications table
- Certifications by vertical chart

### Beauty Certifications Resource
Navigate to: **Beauty Masters > Beauty Certifications**

Features:
- Create, edit, delete certifications
- Filter by type, status, expiry
- View expiring and expired certifications

### Widgets
- **CertificationStatsWidget**: Shows active certifications count per vertical
- **ExpiringCertificationsWidget**: Lists all certifications expiring within 60 days
- **CertificationByVerticalWidget**: Bar chart showing certifications distribution

## Testing

### Running Tests

```bash
# Run all certification tests
./vendor/bin/pest tests/Feature/BeautyCertificationTest.php
./vendor/bin/pest tests/Feature/MakeupCertificationTest.php
./vendor/bin/pest tests/Feature/BrowCertificationTest.php
./vendor/bin/pest tests/Feature/LashCertificationTest.php
```

### Test Coverage

The test suite covers:
- ✅ Creating internal certifications
- ✅ Assigning specializations
- ✅ Verifying certifications
- ✅ Generating development plans
- ✅ Recording test results
- ✅ Blocking masters for services
- ✅ Expiring certificate detection
- ✅ Event dispatching
- ✅ Cache invalidation

## Certification Levels

### Level Definitions

| Level | Description | Can Mentor | Requires Supervision |
|-------|-------------|------------|---------------------|
| Junior | Entry-level specialist | No | Yes |
| Certified | Standard qualified specialist | No | No |
| Advanced | Senior specialist with experience | No | No |
| Master | Expert specialist, can train | Yes | No |
| Expert | Top-level specialist, can train | Yes | No |

### Score-to-Level Mapping

| Total Score | Awarded Level |
|-------------|---------------|
| 95+ | Expert |
| 85-94 | Master |
| 75-84 | Advanced |
| 70-74 | Certified |
| < 70 | Junior |

## Specialization Levels

- **Basic**: Entry-level proficiency
- **Advanced**: Skilled practitioner
- **Expert**: Master-level proficiency

## Required Courses by Specialization

### Beauty
- **Маникюр**: Базовый маникюр, Гигиена и стерилизация, Дизайн ногтей
- **Педикюр**: Медицинский педикюр, Инструментальный педикюр, SPA-педикюр
- **Косметология**: Базовая косметология, Химические пилинги, Массаж лица
- **Визаж**: Базовый макияж, Цветотип внешности, Дневной и вечерний макияж

### Makeup
- **Свадебный макияж**: Базовый макияж, Цветотип внешности, Свадебный образ
- **Вечерний макияж**: Смоки-айс, Блейдинг, Глиттер-макияж
- **Airbrush**: Работа с аэрографом, Тонирующие средства, Пост-контуринг
- **Мужской грим**: Коррекция мужских черт, Стрижка и укладка, Сценический грим
- **Editorial**: Хай-фэшн макияж, Фотографический макияж, Работа с цветом

### Brow
- **Powder Brows**: Основы порошкового напыления, Цветовая коррекция, Стерильность
- **Microblading**: Техника микро-блейдинга, Архитектура брови, Работа с лезвием
- **Henna**: Хна для бровей, Окрашивание хной, Уход после процедуры
- **Lamination**: Ламинирование бровей, Кератиновый состав, Коррекция формы
- **Male Brow**: Мужская коррекция, Окрашивание для мужчин, Формирование

### Lash
- **Classic**: Классическое наращивание, Подбор длины и изгиба, Снятие ресниц
- **Volume 2D-3D**: Объёмное наращивание, Формирование пучков, Карта наращивания
- **Mega Volume**: Мега-объём, Работа с тонкими ресницами, Сложные техники
- **Lash Lifting**: Ламинирование ресниц, Кератиновый состав, Окрашивание
- **Hybrid**: Гибридное наращивание, Смешивание техник, Коррекция

## Caching

Certification verification results are cached for 1 hour to improve performance:

```php
Cache::remember("beauty_certification:{$masterId}:{$specialization}", now()->addHours(1), function () {
    // Verification logic
});
```

Cache is automatically invalidated when:
- Certification is created or updated
- Specialization is assigned or updated
- Certification status changes

## Security & Compliance

### Medical Compliance
- All medical data is handled according to 152-ФZ and ФЗ-323
- No PII is sent to external LLM services
- Sensitive data is anonymized before processing

### Certificate Validation
- Document files are stored securely
- Certificate numbers are unique per vertical
- Expiry dates are automatically tracked

### Audit Logging
All certification operations are logged:
- Certification creation/update/deletion
- Specialization assignments
- Test results
- Status changes
- Master blocking events

## Performance Considerations

### Database Indexes
All tables include appropriate indexes on:
- `master_id` (foreign key)
- `status` (for filtering)
- `expiry_date` (for expiration checks)
- `certification_type` (for filtering)
- Unique constraints on `(master_id, specialization)` for specializations

### Queue Configuration
The expiration check job runs on the `certifications` queue:
```php
$this->onQueue('certifications');
```

Ensure you have a worker configured for this queue:
```bash
php artisan queue:work --queue=certifications
```

## Future Enhancements

- [ ] Integration with external certification bodies
- [ ] Automatic certificate renewal reminders
- [ ] Digital certificate generation with QR codes
- [ ] Public-facing certification verification portal
- [ ] Integration with scheduling system for automatic slot blocking
- [ ] Analytics dashboard for certification trends
- [ ] Multi-language support for certificates

## Troubleshooting

### Certifications not expiring automatically
Ensure the scheduled job is configured correctly:
```bash
php artisan schedule:work
```

### Cache issues
Clear the cache:
```bash
php artisan cache:clear
```

### Filament resources not showing
Ensure the resources are registered in the panel provider:
```php
->resources([
    BeautyCertificationResource::class,
    CertificationDashboardResource::class,
])
```

## Support

For issues or questions, please refer to the main CatVRF documentation or contact the development team.

## License

This module is part of the CatVRF project and follows the project's license terms.
