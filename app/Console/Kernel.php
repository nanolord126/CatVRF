<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use App\Domains\Auto\Jobs\AutoServiceReminderJob;
use App\Domains\Auto\Jobs\CarWashReminderJob;
use App\Jobs\AI\MLRecalculateJob;
use App\Jobs\Analytics\DailyAnalyticsJob;
use App\Jobs\Auto\SurgeRecalculationJob;
use App\Jobs\Taxi\TaxiSurgeRecalculateJob;
use App\Jobs\BonusAccrualJob;
use App\Jobs\CartCleanupJob;
use App\Jobs\CleanupExpiredIdempotencyRecordsJob;
use App\Jobs\DemandForecastJob;
use App\Jobs\Inventory\LowStockAlertJob;
use App\Jobs\Notifications\SendQueuedNotificationsJob;
use App\Jobs\Payments\BatchPayoutJob;
use App\Jobs\Payments\DailyPayoutJob;
use App\Jobs\PaymentReconciliationJob;
use App\Jobs\RecommendationQualityJob;
use App\Jobs\AnnualAnonymizationJob;
use App\Jobs\MarkExpiredCooldownsJob;
use App\Jobs\Analytics\CheckQuotaThresholdsJob;
use App\Jobs\Analytics\ReconcileQuotaUsageJob;
use App\Jobs\InventoryAuditJob;
use App\Jobs\RouteOptimizationJob;
use App\Domains\Bonuses\Jobs\CalculateFloatYieldJob;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use App\Domains\Supermarket\Jobs\ReleaseExpiredReservationsJob;
use Modules\Analytics\Infrastructure\Jobs\CalculateBuyerFeaturesJob;
use Modules\Analytics\Infrastructure\Jobs\RetrainCLVModelJob;
use Modules\Analytics\Infrastructure\Jobs\FineTuneSellerModelsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Filesystem\FilesystemManager;
use Modules\Fitness\Application\Jobs\UpdateTrainerEffectivenessJob;
use Modules\Fitness\Application\Jobs\CheckCertificateExpiryJob;

final class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new SurgeRecalculationJob())
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('taxi-surge-recalculation')
            ->description('Recalculate taxi surge multipliers for active zones');

        // New Clean-Architecture surge job (domain-level)
        $schedule->job(new TaxiSurgeRecalculateJob())
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('taxi-surge-recalculate-domain');

        $schedule->job(new CarWashReminderJob('24h'))
            ->hourly()
            ->withoutOverlapping(10)
            ->name('car-wash-reminder-24h')
            ->description('Send car wash reminders 24 hours before');

        $schedule->job(new CarWashReminderJob('2h'))
            ->everyFifteenMinutes()
            ->withoutOverlapping(5)
            ->name('car-wash-reminder-2h')
            ->description('Send car wash reminders 2 hours before');

        $schedule->job(new AutoServiceReminderJob('24h'))
            ->hourly()
            ->withoutOverlapping(10)
            ->name('auto-service-reminder-24h')
            ->description('Send auto service reminders 24 hours before');

        $schedule->job(new AutoServiceReminderJob('2h'))
            ->everyFifteenMinutes()
            ->withoutOverlapping(5)
            ->name('auto-service-reminder-2h')
            ->description('Send auto service reminders 2 hours before');

        $schedule->job(new DailyPayoutJob())
            ->dailyAt('08:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->name('daily-payout')
            ->description('Process pending payouts for all tenants');

        $schedule->job(new BatchPayoutJob())
            ->everyTwoHours()
            ->withoutOverlapping(30)
            ->name('batch-payout')
            ->description('Process batch withdrawals from mass payout queue');

        $schedule->job(new LowStockAlertJob())
            ->hourly()
            ->withoutOverlapping(15)
            ->name('low-stock-alert')
            ->description('Check inventory items below minimum threshold');

        $schedule->job(new SendQueuedNotificationsJob())
            ->everyTwoMinutes()
            ->withoutOverlapping(5)
            ->name('send-queued-notifications')
            ->description('Process and send queued push/email/SMS notifications');

        $schedule->job(new DailyAnalyticsJob())
            ->dailyAt('03:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->name('daily-analytics')
            ->description('Recalculate forecasts and recommendation embeddings');

        // Seller Analytics: Aggregate daily seller metrics
        $schedule->job(new AggregateSellerDailyMetricsJob())
            ->dailyAt('01:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->name('seller-daily-metrics-aggregation')
            ->description('Aggregate daily metrics for all sellers');

        // Seller Analytics: Aggregate daily product metrics
        $schedule->job(new AggregateSellerProductMetricsJob())
            ->dailyAt('01:30')
            ->timezone('UTC')
            ->withoutOverlapping(90)
            ->onOneServer()
            ->name('seller-product-metrics-aggregation')
            ->description('Aggregate daily metrics for all products');

        $schedule->job(new MLRecalculateJob())
            ->dailyAt('04:30')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->name('ml-recalculate')
            ->description('Train fraud detection ML model on last 30 days');

        // ML Model Retrain with shadow mode and validation (weekly)
        $schedule->command('ml:retrain')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->onOneServer()
            ->name('ml-model-retrain-weekly')
            ->description('Weekly ML model retrain with shadow mode and validation');

        // Promote shadow model to active (24h after retrain)
        $schedule->command('ml:retrain --promote')
            ->weekly()
            ->mondays()
            ->at('03:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->onOneServer()
            ->name('ml-model-promote-shadow')
            ->description('Promote shadow model to active if validation passes');

        $schedule->job(new DemandForecastJob())
            ->dailyAt('04:00')
            ->timezone('UTC')
            ->withoutOverlapping(90)
            ->name('demand-model-train')
            ->description('Train demand forecasting models on historical data');

        $schedule->job(new RecommendationQualityJob())
            ->dailyAt('06:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->name('recommendation-quality')
            ->description('Calculate CTR, conversion rate and revenue lift');

        $schedule->job(new BonusAccrualJob())
            ->dailyAt('01:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->name('turnover-bonus-calculation')
            ->description('Calculate and award bonuses');

        // CatFloat Rewards: Daily bonus unlock (00:05 UTC)
        $schedule->job(new ProcessDailyUnlockJob())
            ->dailyAt('00:05')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->name('catfloat-daily-unlock')
            ->description('Process daily bonus unlocks for all users');

        // CatFloat Rewards: Float yield calculation (00:10 UTC)
        $schedule->job(new CalculateFloatYieldJob())
            ->dailyAt('00:10')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->onOneServer()
            ->name('catfloat-yield-calculation')
            ->description('Calculate and credit float yield from locked bonuses');

        // CatFloat Rewards: Streak processing (00:15 UTC)
        $schedule->job(new ProcessStreakJob())
            ->dailyAt('00:15')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->onOneServer()
            ->name('catfloat-streak-processing')
            ->description('Process user streaks and acceleration bonuses');

        // CatFloat Rewards: Expire old batches (weekly on Sundays at 02:00 UTC)
        $schedule->job(new ExpireOldBatchesJob())
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->name('catfloat-expire-old-batches')
            ->description('Expire fully vested bonus batches older than 90 days');

        $schedule->job(new CleanupExpiredIdempotencyRecordsJob())
            ->dailyAt('00:00')
            ->timezone('UTC')
            ->withoutOverlapping(20)
            ->name('cleanup-idempotency')
            ->description('Remove expired payment idempotency records (older than 24h)');

        $schedule->job(new CartCleanupJob())
            ->everyMinute()
            ->withoutOverlapping(1)
            ->name('cart-cleanup')
            ->description('Release expired cart reservations (20 min TTL)');

        // Route optimization: перерасчёт маршрутов для всех онлайн-курьеров
        $schedule->call(function (): void {
            $activeCouriers = Courier::where('is_online', true)->pluck('id');

            foreach ($activeCouriers as $courierId) {
                $orderIds = DeliveryOrder::where('courier_id', $courierId)
                    ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
                    ->pluck('id')
                    ->toArray();

                if (! empty($orderIds)) {
                    RouteOptimizationJob::$this->bus->dispatch((int) $courierId, $orderIds)
                        ->onQueue('route-opt');
                }
            }
        })
            ->everyThreeMinutes()
            ->withoutOverlapping(2)
            ->name('route-optimization')
            ->description('Recalculate optimized routes for all active couriers');


        $schedule->call(function (): void {
            $storage = $this->app->make(FilesystemManager::class)->disk('models');
            if (! $storage->exists('fraud')) {
                return;
            }

            $files = $storage->files('fraud');

            foreach ($files as $file) {
                $fileAge = time() - $storage->lastModified($file);
                if ($fileAge > 30 * 24 * 60 * 60) {
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

        $schedule->command('log:clear --keep=30')
            ->weekly()
            ->mondays()
            ->at('05:30')
            ->timezone('UTC')
            ->name('cleanup-old-logs')
            ->description('Remove log files older than 30 days');

        $schedule->command('queue:prune-failed --hours=168')
            ->weekly()
            ->mondays()
            ->at('06:00')
            ->timezone('UTC')
            ->name('cleanup-failed-jobs')
            ->description('Remove failed jobs older than 7 days');

        // Плановая инвентаризация всех складов — ежеквартально
        $schedule->job(new InventoryAuditJob())
            ->quarterly()
            ->at('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(240)
            ->onOneServer()
            ->name('inventory-audit-quarterly')
            ->description('Quarterly inventory audit for all active warehouses');

        // Ежегодная анонимизация персональных данных (GDPR / ФЗ-152)
        $schedule->job(new AnnualAnonymizationJob())
            ->yearly()
            ->at('01:00')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->onOneServer()
            ->name('annual-anonymization')
            ->description('Annual GDPR/FZ-152 PII anonymization (users inactive 365+ days)');

        // ClickHouse Quota Analytics - Redis/ClickHouse reconciliation (every minute)
        $schedule->job(new ReconcileQuotaUsageJob())
            ->everyMinute()
            ->withoutOverlapping(5)
            ->onOneServer()
            ->name('quota-reconciliation')
            ->description('Reconcile Redis and ClickHouse quota usage (correct drift >1%)');

        // ClickHouse Quota Analytics - Threshold checking (every minute)
        $schedule->job(new CheckQuotaThresholdsJob())
            ->everyMinute()
            ->withoutOverlapping(5)
            ->onOneServer()
            ->name('quota-threshold-check')
            ->description('Check tenant quota thresholds (85%/95%/100%) and send alerts');

        // Failed Job Monitoring - Check for failed job threshold violations
        $schedule->command('horizon:monitor-failed')
            ->everyFiveMinutes()
            ->withoutOverlapping(5)
            ->onOneServer()
            ->name('failed-job-monitoring')
            ->description('Monitor failed jobs and send alerts if threshold exceeded');

        // Sanctum Token Cleanup - Remove expired tokens daily
        $schedule->command('sanctum:rotate --cleanup')
            ->dailyAt('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('sanctum-token-cleanup')
            ->description('Cleanup expired Sanctum tokens');

        // Cooldown System - Mark expired cooldowns hourly
        $schedule->job(new MarkExpiredCooldownsJob())
            ->hourly()
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('mark-expired-cooldowns')
            ->description('Mark expired cooldown periods as expired');

        // KYB PEP Re-screening - Monthly re-screen of active PEPs
        $schedule->job(new PEPRescreeningJob())
            ->monthly()
            ->on(1)
            ->at('03:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->name('pep-rescreening')
            ->description('Monthly re-screen of active PEP records');

        // KYB Adverse Media Monitoring - Every 6 hours
        $schedule->job(new AdverseMediaMonitoringJob())
            ->everySixHours()
            ->withoutOverlapping(30)
            ->onOneServer()
            ->name('adverse-media-monitoring')
            ->description('Monitor adverse media for all active businesses');

        // Ежегодная анонимизация персональных данных (GDPR / ФЗ-152)
        $schedule->job(new AnnualAnonymizationJob())
            ->yearly()
            ->at('01:00')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->onOneServer()
            ->name('annual-anonymization')
            ->description('Annual GDPR/FZ-152 PII anonymization (users inactive 365+ days)');

        // Fitness: Weekly trainer effectiveness recalculation
        $schedule->job(new UpdateTrainerEffectivenessJob())
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->name('fitness-trainer-effectiveness-recalc')
            ->description('Weekly trainer effectiveness score recalculation');

        // Fitness: Daily certificate expiry check
        $schedule->job(new CheckCertificateExpiryJob())
            ->daily()
            ->at('03:00')
            ->timezone('UTC')
            ->withoutOverlapping(30)
            ->onOneServer()
            ->name('fitness-certificate-expiry-check')
            ->description('Daily check for expiring trainer certifications and specializations');

        // Supermarket: Release expired inventory reservations every 5 minutes
        $schedule->job(new ReleaseExpiredReservationsJob())
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('supermarket-release-expired-reservations')
            ->description('Release expired inventory reservations for Supermarket vertical');

        // CLV: Calculate buyer features daily (feature store update)
        $schedule->job(new CalculateBuyerFeaturesJob())
            ->dailyAt('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->onOneServer()
            ->onQueue(config('analytics.queues.analytics', 'analytics'))
            ->name('clv-calculate-buyer-features')
            ->description('Calculate and update buyer-seller features for CLV prediction');

        // CLV: Retrain ML model weekly
        $schedule->job(new RetrainCLVModelJob())
            ->weekly()
            ->sundays()
            ->at('04:00')
            ->timezone('UTC')
            ->withoutOverlapping(180)
            ->onOneServer()
            ->onQueue(config('analytics.queues.ml_training', 'ml-training'))
            ->name('clv-retrain-model')
            ->description('Retrain CLV prediction model with latest feature data');

        // CLV: Fine-tune seller-specific models (after global retraining)
        $schedule->job(new FineTuneSellerModelsJob())
            ->weekly()
            ->mondays()
            ->at('02:00')
            ->timezone('UTC')
            ->withoutOverlapping(180)
            ->onOneServer()
            ->onQueue(config('analytics.queues.ml_training', 'ml-training'))
            ->name('clv-fine-tune-seller-models')

        // Payment: Daily reconciliation with gateways (if enabled)
        $schedule->job(new PaymentReconciliationJob((int) config('payment.reconciliation_lookback_days', 7)))
            ->cron(config('payment.reconciliation_schedule', '0 2 * * *'))
            ->timezone('UTC')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->onQueue(config('payment.reconciliation_queue', 'payment-reconciliation'))
            ->name('payment-reconciliation')
            ->description('Daily payment reconciliation with gateways (YooKassa, Tinkoff)');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
