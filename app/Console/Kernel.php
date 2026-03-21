<?php declare(strict_types=1);

namespace App\Console;

use App\Jobs\Auto\SurgeRecalculationJob;
use App\Jobs\Beauty\ConsumableDeductionJob;
use App\Jobs\Payments\DailyPayoutJob;
use App\Jobs\Payments\BatchPayoutJob;
use App\Jobs\Inventory\LowStockAlertJob;
use App\Jobs\Notifications\SendQueuedNotificationsJob;
use App\Jobs\Analytics\DailyAnalyticsJob;
use App\Jobs\AI\MLRecalculateJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

final class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // === AUTO VERTICAL ===
        // Recalculate surge pricing every 5 minutes
        $schedule->job(new SurgeRecalculationJob())
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->onOneServer()
            ->name('surge-recalculation')
            ->description('Recalculate surge multipliers for active zones');

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

        // === CLEANUP ===
        // Clean old model files weekly
        $schedule->call(function () {
            $storage = \Illuminate\Support\Facades\Storage::disk('models');
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
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
