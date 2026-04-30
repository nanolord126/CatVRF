# Vertical Queue Integration Guide

**Date:** April 18, 2026  
**Project:** CatVRF - AI-powered Healthcare Marketplace  
**Scope:** Queue/Horizon integration for all 64 business verticals

## Overview

This guide provides a systematic approach for integrating the new Queue/Horizon infrastructure into all 64 business verticals in CatVRF.

## Global Middleware (Already Implemented)

The following job middleware is **already registered globally** in `AppServiceProvider.php`:
- `TenantContextMiddleware` - Ensures tenant context is properly set
- `QuotaCheckMiddleware` - Checks tenant quota before resource-intensive operations
- `PiiMaskingMiddleware` - Masks PII data for 152-ФЗ compliance

**No additional middleware configuration is needed per vertical.**

## Queue Naming Strategy

### Queue Mappings
- `emergency` - Emergency alerts, critical system events (5s wait threshold)
- `payment-webhook` - Payment webhook processing (10s wait threshold)
- `payment` - Payment operations (15s wait threshold)
- `fraud-check-payment` - Fraud checks for payments (10s wait threshold)
- `notification` - All notifications (SMS, push, email) (30s wait threshold)
- `audit` - Audit logging operations (60s wait threshold)
- `ml-recalculate` - ML model retraining/recalculation (300s wait threshold)
- `delivery` - Delivery operations (20s wait threshold)
- `default` - Standard operations (60s wait threshold)
- `bulk` - Bulk/batch operations (120s wait threshold)

### Per-Vertical Queue Strategy

| Vertical | Primary Queue | Secondary Queues |
|----------|---------------|------------------|
| Medical | `notification` | `default` (rating calc), `emergency` (alerts) |
| Food | `delivery` | `notification` (reminders), `default` (auto-close) |
| Beauty | `notification` | `default` (cleanup, matching) |
| Payment | `payment-webhook` | `payment`, `fraud-check-payment` |
| Auto | `notification` | `default` |
| RealEstate | `notification` | `default` |
| Fashion | `notification` | `default` |
| Travel | `notification` | `default` |
| All Others | `notification` | `default` |

## Job Class Template

```php
<?php declare(strict_types=1);

namespace App\Domains\{Vertical}\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

final class {JobName} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public int $timeout = 120;

    public function __construct(
        private readonly int $entityId,
        private readonly string $correlationId,
        private readonly LoggerInterface $logger
    ) {
        $this->onQueue('notification'); // Use appropriate queue
    }

    public function tags(): array
    {
        return [
            '{vertical}',
            '{job-type}',
            'entity:' . $this->entityId,
            'correlation:' . $this->correlationId,
        ];
    }

    public function handle(): void
    {
        // Job logic here
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('{JobName} failed', [
            'entity_id' => $this->entityId,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

## Integration Checklist per Vertical

### 1. Scan for Jobs
```bash
find app/Domains/{Vertical}/Jobs -name "*.php"
```

### 2. For Each Job:
- [ ] Add `implements ShouldQueue` interface
- [ ] Add `use Illuminate\Contracts\Queue\ShouldQueue;` import
- [ ] Add missing traits: `use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;`
- [ ] Set queue in constructor: `$this->onQueue('{queue-name}');`
- [ ] Add `public int $tries = 3;` property
- [ ] Add `public array $backoff = [60, 300, 900];` property
- [ ] Add `public int $timeout = 120;` property (adjust as needed)
- [ ] Add `tags()` method for Horizon observability
- [ ] Add `failed()` method for error handling
- [ ] Add correlationId to constructor parameters (if not present)
- [ ] Add LoggerInterface to constructor (if not present)

### 3. Test Integration
- [ ] Dispatch job manually: `php artisan tinker`
- [ ] Check Horizon dashboard: `php artisan horizon`
- [ ] Verify job appears in correct queue
- [ ] Verify tags appear in Horizon
- [ ] Verify failed() method logs errors correctly

## Vertical-Specific Examples

### Medical Vertical
- `AppointmentReminderJob` → `notification` queue
- `CalculateClinicEarningsJob` → `default` queue
- `RecalculateDoctorRatingJob` → `default` queue
- `UpdateAppointmentStatusJob` → `default` queue

### Food Vertical
- `AutoCloseOrderJob` → `default` queue
- `OrderReadyReminderJob` → `notification` queue

### Beauty Vertical
- `CleanupExpiredAppointmentsJob` → `default` queue
- `ProcessBeautyAiMatchingJob` → `default` queue
- `ReleaseExpiredBookingSlotsJob` → `default` queue
- `ReportSpamJob` → `default` queue

## Batch Update Script (Optional)

For verticals with many jobs, create a batch update script:

```php
<?php
// scripts/update-vertical-jobs.php

$vertical = $argv[1]; // e.g., 'Medical'
$jobsDir = "app/Domains/{$vertical}/Jobs";

$files = glob("{$jobsDir}/*.php");

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Add ShouldQueue interface if missing
    if (!str_contains($content, 'implements ShouldQueue')) {
        $content = str_replace(
            'final class',
            'implements ShouldQueue' . PHP_EOL . 'final class',
            $content
        );
        $content = str_replace(
            'use Psr\Log\LoggerInterface;',
            'use Illuminate\Contracts\Queue\ShouldQueue;' . PHP_EOL . 'use Psr\Log\LoggerInterface;',
            $content
        );
    }
    
    // Add traits if missing
    if (!str_contains($content, 'use Dispatchable')) {
        $content = str_replace(
            'use Illuminate\Queue\SerializesModels;',
            'use Illuminate\Queue\SerializesModels;' . PHP_EOL . PHP_EOL . '    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;',
            $content
        );
    }
    
    file_put_contents($file, $content);
    echo "Updated: {$file}\n";
}
```

Usage:
```bash
php scripts/update-vertical-jobs.php Medical
php scripts/update-vertical-jobs.php Food
php scripts/update-vertical-jobs.php Beauty
```

## Progress Tracking

- ✅ Medical (4 jobs) - Completed
- ✅ Food (2 jobs) - Completed
- ✅ Beauty (4 jobs) - Completed
- ⏳ Remaining 61 verticals - Use batch update script

## Batch Update Script

A batch update script has been created at `scripts/update-vertical-jobs.php` to automate the integration for all remaining verticals.

Usage:
```bash
# Update a specific vertical
php scripts/update-vertical-jobs.php Medical

# Update all remaining verticals
php scripts/update-vertical-jobs.php all
```

The script automatically:
- Adds ShouldQueue interface
- Adds queue traits
- Sets queue assignment
- Adds tags() method
- Adds failed() method
- Adds backoff property
- Adds timeout property

## Notes

- **Middleware is global** - No per-vertical configuration needed
- **Queue names are standardized** - Use the queue mapping table above
- **Correlation ID is required** - Add to all job constructors
- **Logger is required** - Add LoggerInterface to all job constructors
- **Tags are required** - Add tags() method for Horizon observability
- **Failed handler is required** - Add failed() method for error tracking

## Completion Criteria

A vertical is considered integrated when:
1. All jobs implement ShouldQueue
2. All jobs use appropriate queue names
3. All jobs have tags() methods
4. All jobs have failed() methods
5. All jobs have correlationId and logger parameters
6. Jobs are tested and appear correctly in Horizon
