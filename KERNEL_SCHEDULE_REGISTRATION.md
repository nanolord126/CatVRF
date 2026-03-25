<?php

/**
 * KERNEL SCHEDULE REGISTRATION INSTRUCTIONS
 * CANON 2026: Add to app/Console/Kernel.php
 *
 * File: app/Console/Kernel.php
 * In the schedule() method, add:
 */

// ========== Copy-paste this into app/Console/Kernel.php schedule() method ==========

$schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->onOneServer()
    ->withoutOverlapping()
    ->name('taste-profiles-recalculate')
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::channel('audit')->info('ML Taste Profile Job completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('audit')->error('ML Taste Profile Job failed');
        \Sentry\captureException(new \Exception('ML Taste Profile Job failed'));
    });

// ========== Or use alternative timing options ==========

// Every hour
// $schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)->hourly();

// Every 6 hours
// $schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)->everyHours(6);

// Every day at 3 AM
// $schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)->dailyAt('03:00');

// Every Monday at 3 AM
// $schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)->weeklyOn(1, '03:00');

// ========== Full example of what app/Console/Kernel.php should look like ==========

/*

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

final class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Existing schedules...

        // ===== ML Taste Profile Recalculation =====
        $schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)
            ->dailyAt('03:00')
            ->timezone('UTC')
            ->onOneServer()
            ->withoutOverlapping()
            ->name('taste-profiles-recalculate')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::channel('audit')
                    ->info('ML Taste Profile Job completed');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::channel('audit')
                    ->error('ML Taste Profile Job failed');
            });

        // Other schedules...
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

*/

// ========== VERIFICATION COMMANDS ==========

// Test that job can be dispatched:
// php artisan schedule:list
// php artisan schedule:test --name=taste-profiles-recalculate

// Monitor job execution:
// php artisan queue:work

// Check job history in database:
// SELECT * FROM job_batches WHERE name LIKE '%taste%';

// View audit logs:
// tail -f storage/logs/audit.log

// ========== MONITORING QUERIES ==========

// Users needing recalculation:
// SELECT COUNT(*) FROM user_taste_profiles 
// WHERE updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
// AND is_active = 1;

// Average data quality score:
// SELECT AVG(JSON_EXTRACT(metadata, '$.data_quality_score')) as avg_quality
// FROM user_taste_profiles
// WHERE is_active = 1;

// Profile versions:
// SELECT version, COUNT(*) as count
// FROM user_taste_profiles
// GROUP BY version
// ORDER BY version DESC;

// ========== TROUBLESHOOTING ==========

/*

If job doesn't run:
1. Check if schedule is registered in config/app.php:
   - Verify MLServiceProvider is listed in 'providers' array

2. Check if queue worker is running:
   - php artisan queue:work

3. Check Laravel logs:
   - tail -f storage/logs/laravel.log

4. Verify job class exists:
   - php artisan tinker
   - > \App\Jobs\ML\MLRecalculateUserTastesJob::class
   - Should return fully qualified class name

5. Check timezone config in config/app.php:
   - $schedule->timezone should be set
   - Or use ->timezone('UTC') explicitly

If job fails repeatedly:
1. Check TasteMLService availability
2. Verify OpenAI API key is set
3. Check Redis connection
4. Review logs in storage/logs/audit.log

*/

// ========== SLACK NOTIFICATIONS (Optional) ==========

// Add to schedule() method:
/*

$schedule->job(\App\Jobs\ML\MLRecalculateUserTastesJob::class)
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->onOneServer()
    ->onSuccess(function () {
        \Illuminate\Notifications\Notification::send(
            collect([app(\App\Models\User::class)->find(1)]),
            new \App\Notifications\MLJobSuccessNotification()
        );
    })
    ->onFailure(function () {
        \Slack::send('ML Taste Profile Job failed!');
    });

*/

// ========== STEP-BY-STEP CHECKLIST ==========

/*

✅ Checklist before deploying to production:

1. [ ] Verify app/Console/Kernel.php has schedule() entry
2. [ ] Verify MLServiceProvider is in config/app.php providers
3. [ ] Run database migrations: php artisan migrate
4. [ ] Publish config: php artisan vendor:publish --tag=taste-ml-config
5. [ ] Set OPENAI_API_KEY in .env
6. [ ] Configure Redis connection
7. [ ] Set LOG_CHANNEL=audit in .env (optional)
8. [ ] Run: php artisan schedule:list (should show taste-profiles-recalculate)
9. [ ] Start queue worker: php artisan queue:work
10. [ ] Monitor first job execution: tail -f storage/logs/audit.log
11. [ ] Test with: php artisan tinker
    > \App\Models\UserTasteProfile::latest()->first()
    Should show recently updated profile

*/
