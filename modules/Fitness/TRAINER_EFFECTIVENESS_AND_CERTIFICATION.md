# Trainer Effectiveness and Certification System

## Overview

This system provides comprehensive trainer performance evaluation and certification management for the CatVRF fitness vertical. It ensures objective assessment of trainer performance, maintains high safety standards through certification tracking, and supports continuous professional development.

## System Components

### 1. Trainer Effectiveness System

#### Purpose
Automatically evaluate trainer performance using multi-dimensional metrics to provide objective, data-driven insights for management decisions.

#### Metrics (Weighted Formula)

**Client Metrics (60% weight)**
- **Retention Rate (35%)**: Percentage of clients continuing after first month
- **NPS Score (25%)**: Net Promoter Score from client reviews (-100 to 100)
- **Average Check (15%)**: Average revenue per client
- **Repeat Bookings**: Count of returning clients
- **Churn Rate**: Percentage of clients lost

**Operational Metrics (25% weight)**
- **Occupancy Rate**: Schedule fill percentage
- **Average Group Attendance**: Average attendance in group sessions
- **Individual Sessions Count**: Number of 1-on-1 sessions conducted
- **Schedule Compliance**: On-time performance percentage

**Qualitative Metrics (15% weight)**
- **Manager Score**: Supervisor evaluation (0-10 scale)
- **Methodology Compliance**: Adherence to training standards
- **Progress Photos Count**: Client progress documentation

#### Scoring Formula

```
Total Score = (Retention × 0.35) + (NPS × 0.25) + (AvgCheck × 0.15) + (Occupancy × 0.15) + (Manager × 0.10)
```

All scores normalized to 0-100 range before weighting.

#### Effectiveness Levels

| Level | Score Range | Description | Actions |
|-------|-------------|-------------|---------|
| A+ | 90-100 | Top Performer | Bonuses, priority scheduling, mentorship opportunities |
| A | 80-89 | Excellent | Recognition, advanced training access |
| B | 65-79 | Good | Development plan, targeted improvements |
| C | 50-64 | Needs Attention | Performance improvement plan, close monitoring |
| D | <50 | Critical | Temporary suspension, mandatory training |

#### Automation

- **Weekly Recalculation**: `UpdateTrainerEffectivenessJob` runs automatically
- **Low Score Alert**: Automatic notification to manager when score < 65
- **Top Performer Bonus**: Automatic bonus award when score reaches A+
- **Development Plan**: Auto-generated for trainers below target metrics

### 2. Trainer Certification System

#### Purpose
Ensure all trainers maintain valid certifications for their specializations, particularly for sensitive populations (pregnant, children, seniors).

#### Qualification Levels

1. **Junior Trainer**: Entry level, works under supervision
2. **Certified Trainer**: Standard working level
3. **Senior Trainer**: Advanced qualifications, complex programs
4. **Master Trainer**: Top level, can train others, author programs
5. **Specialist**: Narrow specialization (Prenatal, Kids, Senior, etc.)

#### Certification Types

**Internal Certifications**
- Issued by CatVRF after internal testing
- Theory + practical + supervisor evaluation
- Valid for 1-2 years

**External Certifications**
- NASM, ISSA, Les Mills, etc.
- Document upload with verification
- Validity based on issuing organization

#### Specializations

Available specializations:
- **Prenatal**: Pregnancy fitness
- **Kids**: Children's fitness
- **Senior**: Elderly fitness
- **Postpartum**: Post-pregnancy recovery
- **Rehabilitation**: Injury recovery
- **Sports Performance**: Athletic training

#### Safety Controls

- **Automatic Blocking**: Trainers without required certifications cannot lead specialized sessions
- **Expiry Alerts**: 60-day and 30-day warnings before certification expiry
- **On-Hold Status**: Automatic suspension when critical certifications expire
- **Schedule Validation**: System prevents booking trainers without proper qualifications

#### Automation

- **Daily Expiry Check**: `CheckCertificateExpiryJob` runs daily
- **Expiry Notifications**: Automatic alerts to trainer and manager
- **Auto-Verification**: Certifications auto-verified after passing tests
- **Qualification Updates**: Automatic level advancement based on certifications

## Architecture

### Domain Layer (Entities)

**Effectiveness System**
- `TrainerEffectiveness`: Weekly snapshot with all metrics
- `TrainerMetricHistory`: Historical metric changes
- `TrainerReview`: Client reviews for NPS calculation

**Certification System**
- `TrainerCertification`: Certification records
- `TrainerSpecialization`: Specialization assignments
- `TrainerDevelopmentPlan`: Professional development plans
- `CertificationTestResult`: Test results

All entities are `readonly` classes following DDD principles.

### Infrastructure Layer (Models)

Eloquent models with:
- Domain entity conversion (`toDomain()`, `fromDomain()`)
- Relationships defined
- Casts for proper data types
- Soft deletes support

### Application Layer (Services)

**TrainerEffectivenessService**
```php
calculateScore($trainerId, $periodStart, $periodEnd)
saveEffectiveness($effectiveness)
generateDevelopmentPlan($trainerId)
getTopTrainers($limit)
```

**TrainerCertificationService**
```php
verifyCertification($certificationId, $verifiedBy)
checkExpiringCertificates()
generateDevelopmentPlan($trainerId)
assignSpecialization($trainerId, $specialization, $level)
canTrainerLeadSession($trainerId, $sessionType)
processTestResult($testResultId, $theoryScore, $practiceScore, $evaluatorId)
getTrainersNeedingAttention()
```

### Jobs

**UpdateTrainerEffectivenessJob**
- Runs weekly (configurable)
- Calculates scores for all active trainers
- Triggers notifications and bonuses
- Can run for single trainer on-demand

**CheckCertificateExpiryJob**
- Runs daily
- Identifies expiring and expired certifications
- Marks expired certifications
- Places trainers on hold if critical
- Sends notifications

## Database Schema

### Effectiveness Tables

**fitness_trainer_effectiveness**
- Weekly snapshots of trainer performance
- All client, operational, and qualitative metrics
- Total score and effectiveness level
- Period start/end dates

**fitness_trainer_metric_history**
- Tracks metric changes over time
- Old value, new value, delta
- Change reason and notes

**fitness_trainer_reviews**
- Client reviews and ratings
- NPS calculation data
- Sentiment analysis results

### Certification Tables

**fitness_trainer_certifications**
- Internal and external certifications
- Issue and expiry dates
- Verification status
- Document storage

**fitness_trainer_specializations**
- Specialization assignments
- Level (basic, advanced, master)
- Certification linkage
- Active status and expiry

**fitness_trainer_development_plans**
- Professional development goals
- Required courses
- Progress tracking
- Mentor assignment

**fitness_certification_test_results**
- Test scores (theory, practice, total)
- Pass/fail status
- Evaluator feedback

### Trainer Table Updates

**fitness_trainers** (added columns)
- `qualification_level`: Current qualification level
- `is_on_hold`: Suspension status
- `on_hold_since`: Suspension start date
- `on_hold_reason`: Suspension reason

## Usage Examples

### Calculating Trainer Effectiveness

```php
use Modules\Fitness\Application\Services\TrainerEffectivenessService;
use Carbon\CarbonImmutable;

$service = app(TrainerEffectivenessService::class);

// Calculate for last 30 days
$effectiveness = $service->calculateScore(
    trainerId: $trainerId,
    periodStart: CarbonImmutable::now()->subDays(30),
    periodEnd: CarbonImmutable::now(),
);

// Save to database
$saved = $service->saveEffectiveness($effectiveness);

// Check if top performer
if ($saved->isTopPerformer()) {
    // Award bonus
}
```

### Generating Development Plan

```php
$plan = $service->generateDevelopmentPlan($trainerId);

// Returns array of improvement areas
foreach ($plan as $area) {
    echo "Metric: {$area['metric']}\n";
    echo "Current: {$area['current']}, Target: {$area['target']}\n";
    echo "Actions: " . implode(', ', $area['actions']) . "\n";
}
```

### Managing Certifications

```php
use Modules\Fitness\Application\Services\TrainerCertificationService;

$service = app(TrainerCertificationService::class);

// Verify certification
$certification = $service->verifyCertification($certificationId, $managerUserId);

// Assign specialization
$specialization = $service->assignSpecialization(
    trainerId: $trainerId,
    specialization: 'Prenatal',
    level: 'advanced',
);

// Check if can lead session type
$canLead = $service->canTrainerLeadSession($trainerId, 'prenatal');
```

### Processing Test Results

```php
$result = $service->processTestResult(
    testResultId: $testResultId,
    theoryScore: 85,
    practiceScore: 90,
    evaluatorId: $evaluatorId,
    feedback: 'Excellent practical skills',
);

// If passed, certification auto-verified and qualification level updated
```

## Scheduling Integration

### Session Type Validation

When creating schedule slots, the system validates trainer qualifications:

```php
// Before creating a prenatal session
if (!$certificationService->canTrainerLeadSession($trainerId, 'prenatal')) {
    throw new \Exception('Trainer lacks required Prenatal specialization');
}

// Create slot only if validation passes
$scheduleSlot = ScheduleSlot::create([...]);
```

### Critical Certification Expiry

When a critical certification expires (CPR, First Aid, specialized certifications):
1. Trainer automatically placed on hold
2. All future sessions cancelled or reassigned
3. Manager notified immediately
4. Trainer cannot accept new bookings until renewed

## Testing

### Unit Tests

**TrainerEffectivenessServiceTest**
- Score calculation accuracy
- Level classification (A+, A, B, C, D)
- Development plan generation
- Top performers retrieval
- Helper methods (isTopPerformer, requiresAttention, isCritical)

**TrainerCertificationServiceTest**
- Certification verification
- Expiry detection
- Specialization assignment
- Session permission validation
- Test result processing
- On-hold status management

### Integration Tests

**TrainerCertificationSchedulingTest**
- Scheduling validation with specializations
- On-hold blocking
- Expired specialization handling
- Multiple specializations
- Qualification level permissions

Run tests:
```bash
php artisan test tests/Unit/Fitness/
php artisan test tests/Feature/Fitness/
```

## Configuration

### Job Scheduling

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Weekly effectiveness calculation
    $schedule->job(new \Modules\Fitness\Application\Jobs\UpdateTrainerEffectivenessJob())
        ->weekly()
        ->sundays()
        ->at('02:00');

    // Daily certificate expiry check
    $schedule->job(new \Modules\Fitness\Application\Jobs\CheckCertificateExpiryJob())
        ->daily()
        ->at('03:00');
}
```

### Environment Variables

No specific environment variables required. System uses standard Laravel configuration.

## Security & Compliance

### Medical Data Protection
- No PII sent to external AI services
- Client reviews anonymized before analysis
- Audit logging for all certification changes

### Safety Compliance
- Critical certifications (CPR, First Aid) tracked separately
- Automatic blocking for expired safety certifications
- Specialization validation for vulnerable populations

### Audit Trail
- All certification changes logged
- Effectiveness score changes tracked in history
- Manager actions recorded with user ID and timestamp

## Performance Considerations

### Caching
- Effectiveness scores cached for 24 hours
- Top trainers list cached for 1 hour
- Certification status cached per session

### Database Indexes
- Composite indexes on trainer_id + period_start
- Indexes on total_score for top performers
- Indexes on expiry_date for expiry checks
- Indexes on status for filtering

### Query Optimization
- Eager loading relationships to prevent N+1
- Batch processing for weekly calculations
- Chunked queries for large trainer pools

## Monitoring & Alerts

### Key Metrics to Monitor
- Average trainer effectiveness score
- Percentage of trainers with expired certifications
- Number of trainers on hold
- Development plan completion rate
- Certification renewal rate

### Alert Thresholds
- Alert if average effectiveness < 70
- Alert if >10% of trainers have expired certs
- Alert if >5% of trainers on hold
- Alert if certification renewal rate < 80%

## Future Enhancements

### Planned Features
1. **AI-Powered Recommendations**: Use ML to suggest personalized improvements
2. **Peer Reviews**: Add peer-to-peer evaluation component
3. **Client Retention Prediction**: Predict which clients might churn
4. **Certification Marketplace**: Integration with external training providers
5. **Mobile App**: Trainer-facing mobile app for self-service
6. **Advanced Analytics**: Power BI / Tableau integration for deep insights

### Integration Opportunities
- **Loyalty System**: Top trainers give clients bonus points
- **Payment System**: Automatic bonus calculation and disbursement
- **HR System**: Sync qualification data with HR records
- **Learning Management**: Integration with training platforms

## Troubleshooting

### Common Issues

**Issue: Effectiveness score not calculating**
- Check if trainer has bookings/attendances in period
- Verify data in fitness_bookings and fitness_attendances tables
- Check logs for calculation errors

**Issue: Certification not verified**
- Ensure test result passed (score >= 70)
- Check if certification_id is linked to test result
- Verify evaluator_id is valid user

**Issue: Trainer not blocked despite expired cert**
- Check if certification is marked as critical
- Verify is_on_hold field updated
- Check job execution logs

**Issue: Development plan not generating**
- Ensure trainer has effectiveness data
- Check if metrics are below targets
- Verify service is called correctly

## Support & Maintenance

### Regular Maintenance Tasks
1. Review and adjust score weights quarterly
2. Update certification requirements annually
3. Clean up old effectiveness snapshots (keep 2 years)
4. Review and update specializations list
5. Audit trainer qualification levels

### Data Retention
- Effectiveness snapshots: 2 years
- Metric history: 2 years
- Reviews: 3 years
- Certification records: 5 years after expiry
- Test results: 5 years

## Version History

- **v1.0.0** (2026-04-23): Initial implementation
  - Effectiveness scoring system
  - Certification management
  - Automated jobs
  - Comprehensive testing
  - Full documentation

## References

- CatVRF Coding Standards
- Clean Architecture principles
- DDD (Domain-Driven Design)
- Laravel Best Practices
- 152-ФЗ compliance requirements
- ФЗ-323 healthcare regulations
