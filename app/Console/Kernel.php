<?php declare(strict_types=1);

namespace App\Console;

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
use App\Jobs\RecommendationQualityJob;
use App\Jobs\AnnualAnonymizationJob;
use App\Jobs\InventoryAuditJob;
use App\Jobs\RouteOptimizationJob;
use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\DeliveryOrder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Storage;

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

        $schedule->job(new MLRecalculateJob())
            ->dailyAt('04:30')
            ->timezone('UTC')
            ->withoutOverlapping(120)
            ->name('ml-recalculate')
            ->description('Train fraud detection ML model on last 30 days');

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

                if (!empty($orderIds)) {
                    RouteOptimizationJob::dispatch((int) $courierId, $orderIds)
                        ->onQueue('route-opt');
                }
            }
        })
            ->everyThreeMinutes()
            ->withoutOverlapping(2)
            ->name('route-optimization')
            ->description('Recalculate optimized routes for all active couriers');


        $schedule->call(function (): void {
            $storage = Storage::disk('models');
            if (!$storage->exists('fraud')) {
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
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
