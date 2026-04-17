<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Tenancy\TenantQuotaNotificationService;
use Illuminate\Console\Command;

/**
 * Check Quota Thresholds Command
 *
 * Production 2026 CANON - Scheduled Task
 *
 * Checks quota thresholds and sends notifications to tenants
 * approaching their limits. Should be scheduled to run every hour.
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class CheckQuotaThresholdsCommand extends Command
{
    protected $signature = 'quota:check-thresholds';

    protected $description = 'Check quota thresholds and send notifications';

    public function handle(TenantQuotaNotificationService $notificationService): int
    {
        $this->info('Checking quota thresholds...');

        $notificationsSent = $notificationService->checkThresholds();

        $this->info("Sent {$notificationsSent} quota threshold notifications.");

        return self::SUCCESS;
    }
}
