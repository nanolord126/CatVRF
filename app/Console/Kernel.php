<?php declare(strict_types=1);

namespace App\Console;

use App\Domains\Taxi\Jobs\SurgeRecalculationJob;
use App\Domains\Taxi\Jobs\TaxiRideReminderJob;
use App\Domains\Auto\Jobs\CarWashReminderJob;
use App\Domains\Auto\Jobs\AutoServiceReminderJob;
use App\Jobs\Beauty\ConsumableDeductionJob;
use App\Jobs\Payments\DailyPayoutJob;
use App\Jobs\Payments\BatchPayoutJob;
use App\Jobs\Payments\CleanupExpiredIdempotencyRecordsJob;
use App\Jobs\Inventory\LowStockAlertJob;
use App\Jobs\Notifications\SendQueuedNotificationsJob;
use App\Jobs\Notifications\ReminderNotificationJob;
use App\Jobs\Analytics\DailyAnalyticsJob;
use App\Jobs\Analytics\BigDataAggregatorJob;
use App\Jobs\AI\MLRecalculateJob;
use App\Jobs\AI\EmbeddingsRecalculateJob;
use App\Jobs\AI\RecommendationQualityJob;
use App\Jobs\AI\DemandModelTrainJob;
use App\Jobs\Bonuses\TurnoverBonusCalculationJob;
use App\Jobs\Referrals\ReferralQualificationCheckJob;
use App\Jobs\Promos\ExpiredPromoCampaignCheckJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Storage;

final class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // === TAXI VERTICAL ===
        // Recalculate surge pricing every 5 minutes
        $schedule->job(new SurgeRecalculationJob())
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('taxi-surge-recalculation')
            ->description('Recalculate taxi surge multipliers for active zones');

        // === AUTO VERTICAL ===
        $schedule->job(new CarWashReminderJob('24h'))
            ->hourly()
            ->withoutOverlapping(10)
            ->name('car-wash-reminder-24h')
            ->description('Send car wash reminders 24 hours before');

        // Car wash reminders (2h before)
        $schedule->job(new CarWashReminderJob('2h'))
            ->everyFifteenMinutes()
            ->withoutOverlapping(5)
            ->name('car-wash-reminder-2h')
            ->description('Send car wash reminders 2 hours before');

        // Auto service reminders (24h before)
        $schedule->job(new AutoServiceReminderJob('24h'))
            ->hourly()
            ->withoutOverlapping(10)
            ->name('auto-service-reminder-24h')
            ->description('Send auto service reminders 24 hours before');

        // Auto service reminders (2h before)
        $schedule->job(new AutoServiceReminderJob('2h'))
            ->everyFifteenMinutes()
            ->withoutOverlapping(5)
            ->name('auto-service-reminder-2h')
            ->description('Send auto service reminders 2 hours before');

        // === PAYMENTS ===
        // Daily payout batch at 08:00 UTC
        $schedule->job(new DailyPayoutJob())
            ->dailyAt('08:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->name('daily-payout')
            ->description('Process pending payouts for all tenants');

        // Mass payout batch every 2 hours
        $schedule->job(new BatchPayoutJob())
            ->everyTwoHours()
            ->withoutOverlapping(30)
            ->name('batch-payout')
            ->description('Process batch withdrawals from mass payout queue');

        // === INVENTORY ===
        // Low stock alert check every hour
        $schedule->job(new LowStockAlertJob())
            ->hourly()
            ->withoutOverlapping(15)
            ->name('low-stock-alert')
            ->description('Check inventory items below minimum threshold');

        // === NOTIFICATIONS ===
        // Send queued notifications every 2 minutes
        $schedule->job(new SendQueuedNotificationsJob())
            ->everyTwoMinutes()
            ->withoutOverlapping(5)
            ->name('send-queued-notifications')
            ->description('Process and send queued push/email/SMS notifications');

        // === ANALYTICS & AI ===
        // Daily analytics recalculation at 03:00 UTC
        $schedule->job(new DailyAnalyticsJob())
            ->dailyAt('03:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->name('daily-analytics')
            ->description('Recalculate forecasts and recommendation embeddings');

        // ML model retraining daily at 04:30 UTC
        $schedule->job(new MLRecalculateJob())
            ->dailyAt('04:30')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->name('ml-recalculate')
            ->description('Train fraud detection ML model on last 30 days');

        // Embeddings recalculation daily at 03:30 UTC
        $schedule->job(new EmbeddingsRecalculateJob())
            ->dailyAt('03:30')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->name('embeddings-recalculate')
            ->description('Recalculate all product/service embeddings for recommendations');

        // Demand forecast model training daily at 04:00 UTC
        $schedule->job(new DemandModelTrainJob())
            ->dailyAt('04:00')
            ->timezone('UTC')
            ->withoutOverlapping(90)
            ->name('demand-model-train')
            ->description('Train demand forecasting models on historical data');

        // BigData aggregation daily at 02:00 UTC
        $schedule->job(new BigDataAggregatorJob())
            ->dailyAt('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->name('bigdata-aggregator')
            ->description('Aggregate events to ClickHouse for analytics');

        // Recommendation quality check daily at 06:00 UTC
        $schedule->job(new RecommendationQualityJob())
            ->dailyAt('06:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->name('recommendation-quality')
            ->description('Calculate CTR, conversion rate and revenue lift');

        // === BONUSES & REFERRALS ===
        // Turnover bonus calculation daily at 01:00 UTC
        $schedule->job(new TurnoverBonusCalculationJob())
            ->dailyAt('01:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->name('turnover-bonus-calculation')
            ->description('Calculate and award turnover-based bonuses');

        // Referral qualification check hourly
        $schedule->job(new ReferralQualificationCheckJob())
            ->hourly()
            ->withoutOverlapping(15)
            ->name('referral-qualification-check')
            ->description('Check if referrals reached threshold for bonus');

        // === PROMO CAMPAIGNS ===
        // Check expired promo campaigns every hour
        $schedule->job(new ExpiredPromoCampaignCheckJob())
            ->hourly()
            ->withoutOverlapping(10)
            ->name('expired-promo-check')
            ->description('Mark expired promo campaigns as exhausted');

        // === APPOINTMENTS & REMINDERS ===
        // Send appointment reminders (24h before)
        $schedule->job(new ReminderNotificationJob('24h'))
            ->hourly()
            ->withoutOverlapping(10)
            ->name('reminder-24h')
            ->description('Send reminders 24 hours before appointments');

        // Send appointment reminders (2h before)
        $schedule->job(new ReminderNotificationJob('2h'))
            ->everyFifteenMinutes()
            ->withoutOverlapping(5)
            ->name('reminder-2h')
            ->description('Send reminders 2 hours before appointments');

        // === PAYMENTS SECURITY ===
        // Cleanup expired idempotency records daily at 00:00 UTC
        $schedule->job(new CleanupExpiredIdempotencyRecordsJob())
            ->dailyAt('00:00')
            ->timezone('UTC')
            ->withoutOverlapping(20)
            ->name('cleanup-idempotency')
            ->description('Remove expired payment idempotency records (older than 24h)');

        // === CLEANUP ===
        // Clean old model files weekly
        $schedule->call(function () {
            $storage = $this->storage->disk('models');
            if (!$storage->exists('fraud')) {
                return;
            }
            
            $files = $storage->files('fraud');

            foreach ($files as $file) {
                $fileAge = time() - $storage->lastModified($file);
                if ($fileAge > 30 * 24 * 60 * 60) { // 30 days
                    $storage->delete($file);
                }
            }
        })
            ->weekly()
            ->mondays()
            ->at('05:00')
            ->timezone('UTC')
            ->name('cleanup-old-models')
            ->description('Remove ML model files older than 30 days');

        // Clean old logs weekly
        $schedule->command('log:clear --keep=30')
            ->weekly()
            ->mondays()
            ->at('05:30')
            ->timezone('UTC')
            ->name('cleanup-old-logs')
            ->description('Remove log files older than 30 days');

        // Clean failed jobs weekly
        $schedule->command('queue:prune-failed --hours=168')
            ->weekly()
            ->mondays()
            ->at('06:00')
            ->timezone('UTC')
            ->name('cleanup-failed-jobs')
            ->description('Remove failed jobs older than 7 days');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
