# Professional Development and Breed Certification System

## Overview

The Professional Development and Breed Certification system is a comprehensive solution for tracking, managing, and enhancing the qualifications of veterinarians and groomers in the CatVRF platform. This system ensures compliance with mandatory training, supports career progression, and enables specialized breed-specific certifications for groomers.

## Table of Contents

- [Architecture](#architecture)
- [Professional Development Module](#professional-development-module)
- [Breed Certification Module](#breed-certification-module)
- [Installation](#installation)
- [Usage Examples](#usage-examples)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Compliance and Security](#compliance-and-security)

## Architecture

This system follows **Clean Architecture** with **Domain-Driven Design (DDD)** principles:

```
modules/VetGrooming/
├── Domain/
│   ├── Entities/           # Readonly domain entities
│   ├── Events/             # Domain events
│   └── Repositories/       # Repository interfaces
├── Infrastructure/
│   └── Models/             # Eloquent models with toDomain/fromDomain
├── Application/
│   ├── Services/           # Business logic services
│   ├── Jobs/               # Queue jobs
│   └── Livewire/           # Real-time components
└── Filament/
    └── Resources/          # Admin UI resources
```

## Professional Development Module

### Features

- **Development Plans**: Personalized 12-month development plans for vets and groomers
- **Training Courses**: Internal CatCRM courses and external certifications
- **Training Completions**: Track course completions with scores and certificates
- **Skill Matrices**: Track individual skill proficiency levels
- **Compliance Checking**: Automatic verification of mandatory courses
- **Development Scoring**: Comprehensive scoring based on completion, quality, skills, and timeliness
- **Level Progression**: Automatic detection and notification of level-ups

### Database Schema

#### professional_development_plans
- `master_id`: Reference to veterinarian/groomer
- `profession_type`: 'vet' or 'groomer'
- `current_level`: 'junior', 'intermediate', 'senior', 'expert', 'master'
- `target_level`: Target qualification level
- `completion_percentage`: Plan progress (0-100)
- `effectiveness_score`: Current effectiveness rating
- `development_score`: Calculated development progress score

#### training_courses
- `title`: Course name
- `category`: 'internal' or 'external'
- `profession_type`: Target profession
- `is_mandatory`: Whether course is mandatory
- `mandatory_for_specializations`: Specializations requiring this course
- `mandatory_for_breeds`: Breed groups requiring this course

#### training_completions
- `master_id`: Who completed the course
- `course_id`: Which course was completed
- `score`: Test score (0-100)
- `certificate_expiry_date`: Certificate validity period
- `verification_status`: 'pending', 'verified', 'rejected'

#### skill_matrices
- `master_id`: Master reference
- `skill`: Skill name
- `proficiency_level`: 'beginner', 'intermediate', 'advanced', 'expert'
- `practice_hours`: Hours of practice in this skill

### Service Methods

#### ProfessionalDevelopmentService

```php
// Generate a development plan
$plan = $service->generateDevelopmentPlan(
    masterId: 1,
    professionType: 'vet',
    tenantId: 1,
    currentLevel: 'junior',
    targetLevel: 'intermediate'
);

// Check mandatory course compliance
$compliance = $service->checkMandatoryCoursesCompliance(
    masterId: 1,
    professionType: 'groomer',
    tenantId: 1
);
// Returns: ['compliant' => bool, 'missing_courses' => [], 'expired' => []]

// Calculate development score
$score = $service->calculateDevelopmentScore(masterId: 1, tenantId: 1);

// Update plan progress
$updatedPlan = $service->updatePlanProgress(planId: 1);

// Add training completion
$completion = $service->addTrainingCompletion(
    masterId: 1,
    courseId: 1,
    developmentPlanId: 1,
    score: 95,
    durationHours: 10
);

// Update skill
$skill = $service->updateSkill(
    masterId: 1,
    skill: 'Basic examination',
    proficiencyLevel: 'intermediate'
);
```

### Events

- **MasterLevelUp**: Dispatched when a master achieves a new qualification level

### Jobs

- **CheckMandatoryCoursesJob**: Runs monthly to check compliance with mandatory courses
- Schedules: `php artisan schedule:run` (configured in app/Console/Kernel.php)

## Breed Certification Module

### Features

- **Breed Certifications**: Track certifications for specific breeds or breed groups
- **Certification Levels**: Basic, Certified, Advanced, Master
- **Booking Blocking**: Automatically block bookings for breeds requiring higher certification
- **Breed Groups**: Pre-defined breed groups (terriers, spitz, poodles, brachycephalic, etc.)
- **Development Plans**: Personalized plans for breed certification
- **Certification Matrix**: Visual overview of all breed certifications
- **Expiry Tracking**: Automatic detection and notification of expiring certifications

### Database Schema

#### breed_certifications
- `master_id`: Groomer reference
- `certification_type`: 'breed_group' or 'specific_breed'
- `breed_group`: Breed group (e.g., 'terriers', 'spitz')
- `breed_id`: Specific breed if type is 'specific_breed'
- `certification_level`: 'basic', 'certified', 'advanced', 'master'
- `issue_date`, `expiry_date`: Certification validity
- `practical_exam_score`, `theory_exam_score`: Exam results
- `status`: 'active', 'expired', 'pending_verification', 'revoked', 'suspended'

#### breed_specializations
- `master_id`: Groomer reference
- `breed_group`: Breed group
- `proficiency_level`: 'beginner', 'intermediate', 'advanced', 'expert'
- `total_groomings_completed`: Count of grooming sessions
- `certifications_count`: Number of certifications in this group
- `average_rating`: Performance rating for this breed

#### breed_development_plans
- `master_id`: Groomer reference
- `target_breed_group`: Target breed group
- `target_level`: Target certification level
- `training_hours_completed`, `practical_hours_completed`: Progress tracking
- `required_training_hours`, `required_practical_hours`: Requirements
- `mentor_id`: Assigned mentor

### Service Methods

#### BreedCertificationService

```php
// Check if groomer has certification for a breed
$hasCertification = $service->hasCertificationForBreed(
    masterId: 1,
    petSpecies: 'dogs',
    petBreed: 'pomeranian',
    requiredLevel: 'certified'
);

// Get available groomers for a breed
$groomerIds = $service->getAvailableGroomersForBreed(
    petSpecies: 'dogs',
    petBreed: 'pomeranian',
    requiredLevel: 'certified',
    tenantId: 1
);

// Verify and create breed certification
$certification = $service->verifyBreedCertification(
    masterId: 1,
    certificationType: 'breed_group',
    certificationLevel: 'certified',
    tenantId: 1,
    breedGroup: 'spitz',
    practicalScore: 85,
    theoryScore: 90
);

// Block booking if no certification
$result = $service->blockBookingIfNoCertification(
    masterId: 1,
    petSpecies: 'dogs',
    petBreed: 'pug'
);
// Returns: ['blocked' => bool, 'reason' => string, 'alternative_groomers' => []]

// Create breed development plan
$plan = $service->createBreedDevelopmentPlan(
    masterId: 1,
    targetBreedGroup: 'terriers',
    targetLevel: 'advanced',
    tenantId: 1,
    mentorId: 5
);

// Get breed certification matrix
$matrix = $service->getBreedCertificationMatrix(masterId: 1, tenantId: 1);
```

### Breed Groups

The system includes pre-defined breed groups:

**Dogs:**
- **Terriers**: yorkshire_terrier, jack_russell, fox_terrier, bedlington_terrier
- **Spitz**: pomeranian, samoyed, husky, malamute, chow_chow, akita
- **Poodles**: poodle, bichon_frisee, lagotto_romagnolo
- **Brachycephalic**: pug, french_bulldog, english_bulldog, boston_terrier
- **Long-haired**: maltese, shih_tzu, afghan_hound, yorkshire_terrier, lhasa_apso
- **Wire-haired**: schnauzer, airedale_terrier, fox_terrier_wire, scottish_terrier
- **Short-haired**: boxer, doberman, labrador, staffordshire_bull_terrier, great_dane
- **Shepherds**: german_shepherd, belgian_shepherd, rottweiler, border_collie

**Cats:**
- **Long-haired**: maine_coon, persian, siberian, norwegian_forest_cat, ragdoll
- **Short-haired**: british_shorthair, scottish_fold, abyssinian, siamese, bengal
- **Hairless**: sphynx, peterbald, don_sphynx, elf_cat

### Required Certification Levels

Certain breed groups require higher certification levels:

- **Brachycephalic**: Advanced (breathing risks, special handling)
- **Terriers**: Certified (proper coat handling)
- **Spitz**: Certified (double coat management)
- **Long-haired**: Intermediate (proper grooming)
- **Hairless**: Advanced (special skin care)

### Events

- **BreedCertificationExpired**: Dispatched when a certification expires

### Jobs

- **CheckBreedCertificationsJob**: Runs monthly to check for expiring certifications
- Schedules: `php artisan schedule:run` (configured in app/Console/Kernel.php)

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `professional_development_plans`
- `training_courses`
- `training_completions`
- `skill_matrices`
- `breed_certifications`
- `breed_specializations`
- `breed_development_plans`

### 2. Register Filament Resources

The resources are auto-discovered if following Laravel conventions. Verify in `config/filament.php`:

```php
'resources' => [
    // ... existing resources
    Modules\VetGrooming\Filament\Resources\ProfessionalDevelopmentPlanResource::class,
    Modules\VetGrooming\Filament\Resources\TrainingCourseResource::class,
    Modules\VetGrooming\Filament\Resources\TrainingCompletionResource::class,
],
```

### 3. Schedule Jobs

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Check mandatory courses monthly
    $schedule->job(new \Modules\VetGrooming\Application\Jobs\CheckMandatoryCoursesJob($tenantId))
        ->monthly()
        ->at('00:00');

    // Check breed certifications monthly
    $schedule->job(new \Modules\VetGrooming\Application\Jobs\CheckBreedCertificationsJob($tenantId))
        ->monthly()
        ->at('00:00');
}
```

### 4. Register Livewire Component

Add to `resources/views/livewire/breed-certification-matrix.blade.php` and register in your Livewire configuration.

## Usage Examples

### Example 1: Veterinarian Development Plan

```php
use Modules\VetGrooming\Application\Services\ProfessionalDevelopmentService;
use Carbon\CarbonImmutable;

$service = app(ProfessionalDevelopmentService::class);

// Create a development plan for a junior dermatologist
$plan = $service->generateDevelopmentPlan(
    masterId: 1,
    professionType: 'vet',
    tenantId: 1,
    currentLevel: 'junior',
    targetLevel: 'senior',
    effectivenessScore: 75.5
);

// Add training completion
$service->addTrainingCompletion(
    masterId: 1,
    courseId: 5,
    developmentPlanId: $plan->id,
    score: 92,
    durationHours: 15,
    certificateFile: 'certificates/dermatology_advanced.pdf',
    certificateExpiryDate: CarbonImmutable::now()->addYears(2)
);

// Update skills
$service->updateSkill(
    masterId: 1,
    skill: 'Dermatological examination',
    proficiencyLevel: 'advanced',
    skillCategory: 'medical',
    practiceHours: 50
);

// Calculate development score
$score = $service->calculateDevelopmentScore(1, 1);
echo "Development Score: {$score}%";
```

### Example 2: Groomer Breed Certification

```php
use Modules\VetGrooming\Application\Services\BreedCertificationService;

$service = app(BreedCertificationService::class);

// Verify breed certification
$certification = $service->verifyBreedCertification(
    masterId: 1,
    certificationType: 'breed_group',
    certificationLevel: 'advanced',
    tenantId: 1,
    breedGroup: 'brachycephalic',
    practicalScore: 88,
    theoryScore: 92,
    examFeedback: 'Excellent handling of pugs and french bulldogs'
);

// Create development plan for new breed
$plan = $service->createBreedDevelopmentPlan(
    masterId: 1,
    targetBreedGroup: 'terriers',
    targetLevel: 'advanced',
    tenantId: 1,
    mentorId: 5
);

// Check booking eligibility
$result = $service->blockBookingIfNoCertification(
    masterId: 2,
    petSpecies: 'dogs',
    petBreed: 'pug'
);

if ($result['blocked']) {
    // Suggest alternative groomers
    $alternatives = $service->getAvailableGroomersForBreed(
        petSpecies: 'dogs',
        petBreed: 'pug',
        requiredLevel: 'advanced',
        tenantId: 1
    );
}
```

### Example 3: Booking Integration

```php
// In your appointment booking service
public function bookAppointment(Appointment $appointment)
{
    $breedCertService = app(BreedCertificationService::class);
    
    $result = $breedCertService->blockBookingIfNoCertification(
        $appointment->groomer_id,
        $appointment->pet->species,
        $appointment->pet->breed
    );
    
    if ($result['blocked']) {
        throw new BookingBlockedException(
            $result['message'],
            alternativeGroomers: $result['alternative_groomers']
        );
    }
    
    // Proceed with booking
    $appointment->save();
}
```

### Example 4: Livewire Component Usage

```blade
<!-- In your blade template -->
<livewire:breed-certification-matrix :master-id="$groomer->id" />
```

```php
// In your controller
public function showGroomerProfile(Groomer $groomer)
{
    return view('groomers.profile', [
        'groomer' => $groomer,
        'certificationMatrix' => app(BreedCertificationService::class)
            ->getBreedCertificationMatrix($groomer->id, auth()->user()->tenant_id)
    ]);
}
```

## API Reference

### ProfessionalDevelopmentPlan Entity

```php
final readonly class ProfessionalDevelopmentPlan
{
    public function activate(): self
    public function complete(): self
    public function updateProgress(int $coursesCompleted, int $totalCoursesRequired): self
    public function updateScores(?float $effectivenessScore, ?float $developmentScore): self
    public function isComplete(): bool
    public function isActive(): bool
    public function isOverdue(): bool
    public function hasLevelUp(): bool
}
```

### BreedCertification Entity

```php
final readonly class BreedCertification
{
    public function verify(int $verifiedBy, ?string $certificateNumber = null): self
    public function expire(): self
    public function revoke(): self
    public function addExamScores(?float $practicalScore, ?float $theoryScore, ?string $feedback = null): self
    public function setExpiryDate(CarbonImmutable $expiryDate): self
    public function isActive(): bool
    public function isExpired(): bool
    public function isExpiringWithin(int $days): bool
    public function isAtLeastLevel(string $level): bool
}
```

## Testing

### Running Tests

```bash
# Run all VetGrooming tests
php artisan test --filter=VetGrooming

# Run Professional Development tests
php artisan test tests/Unit/VetGrooming/ProfessionalDevelopmentServiceTest.php

# Run Breed Certification tests
php artisan test tests/Unit/VetGrooming/BreedCertificationServiceTest.php
```

### Test Coverage

The system maintains ≥95% test coverage with comprehensive unit tests for:
- Service methods
- Domain entity methods
- Business logic edge cases
- Compliance checking
- Scoring algorithms

### Test Examples

```php
public function test_has_certification_for_breed_with_specific_certification(): void
{
    $result = $this->service->hasCertificationForBreed(
        masterId: 1,
        petSpecies: 'dogs',
        petBreed: 'pomeranian'
    );
    
    $this->assertTrue($result);
}

public function test_block_booking_if_no_certification_blocked(): void
{
    $result = $this->service->blockBookingIfNoCertification(
        masterId: 1,
        petSpecies: 'dogs',
        petBreed: 'pug' // Requires advanced certification
    );
    
    $this->assertTrue($result['blocked']);
    $this->assertEquals('brachycephalic', $result['breed_group']);
}
```

## Compliance and Security

### Medical Compliance (152-ФЗ, ФЗ-323)

- **No PII in External Services**: Medical data is never sent to external LLMs
- **Certificate Expiry Tracking**: Automatic detection of expired certifications
- **Audit Logging**: All certification verifications and level-ups are logged

### Fraud Prevention

- **Certificate Verification**: All external certifications require verification
- **Score Validation**: Practical and theory exam scores are validated
- **Audit Trail**: Complete audit trail for all certification changes

### Performance Optimization

- **Caching**: Development scores and available groomers are cached
- **Queue Jobs**: Compliance checks run asynchronously
- **Efficient Queries**: Optimized database queries with proper indexing

## Best Practices

### 1. Regular Compliance Checks

Schedule monthly compliance checks to ensure all mandatory courses are current:

```php
// In your scheduled tasks
CheckMandatoryCoursesJob::dispatch($tenantId);
CheckBreedCertificationsJob::dispatch($tenantId);
```

### 2. Prompt Certificate Renewal

Notify masters 30 days before certificate expiry:

```php
$expiringCertifications = $certificationRepository->findExpiringWithin(30, $tenantId);
foreach ($expiringCertifications as $certification) {
    Notification::send($certification->master, new CertificateExpiringNotification($certification));
}
```

### 3. Integrate with Scheduling

Block appointments when certifications are expired:

```php
if (!$breedCertService->hasCertificationForBreed($groomerId, $species, $breed)) {
    throw new CertificationRequiredException();
}
```

### 4. Track Development Progress

Monitor development scores to identify training gaps:

```php
$score = $service->calculateDevelopmentScore($masterId, $tenantId);
if ($score < 70) {
    // Trigger additional training recommendations
}
```

## Troubleshooting

### Issue: Development score not updating

**Solution**: Clear the cache:
```bash
php artisan cache:clear
php artisan schedule:run
```

### Issue: Booking not blocked for uncertified groomer

**Solution**: Verify breed group mapping:
```php
$breedGroup = $service->getBreedGroup($species, $breed);
// Returns null if breed is not in the predefined list
```

### Issue: Certificate expiry not detected

**Solution**: Check certificate_expiry_date is set correctly and not null:
```php
$completion->certificateExpiryDate = CarbonImmutable::now()->addYears(2);
```

## Future Enhancements

- [ ] Integration with external training platforms (VetEdu, Grooming Academy)
- [ ] AI-powered course recommendations based on skill gaps
- [ ] Gamification elements for training completion
- [ ] Mobile app for masters to track their progress
- [ ] Advanced analytics dashboard for clinic owners
- [ ] Integration with payroll and compensation systems
- [ ] Multi-tenant certification standards

## Support

For issues or questions related to this module:
1. Check this documentation
2. Review test files for usage examples
3. Check logs in `storage/logs/laravel.log`
4. Contact the development team

## License

This module is part of the CatVRF platform and follows the same license terms.
