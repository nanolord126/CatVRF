<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\BeautyMasters\Application\Services\BeautyCertificationService;
use Modules\BeautyMasters\Application\Services\MakeupCertificationService;
use Modules\BeautyMasters\Application\Services\BrowCertificationService;
use Modules\BeautyMasters\Application\Services\LashCertificationService;
use Illuminate\Support\Facades\Log;

final readonly class CheckExpiringCertificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('certifications');
    }

    public function handle(
        BeautyCertificationService $beautyCertificationService,
        MakeupCertificationService $makeupCertificationService,
        BrowCertificationService $browCertificationService,
        LashCertificationService $lashCertificationService,
    ): void {
        Log::info('Starting expiring certifications check');

        try {
            $beautyCertificationService->checkExpiringCertificates();
            Log::info('Beauty certifications check completed');
        } catch (\Exception $e) {
            Log::error('Error checking beauty certifications', ['error' => $e->getMessage()]);
        }

        try {
            $makeupCertificationService->checkExpiringCertificates();
            Log::info('Makeup certifications check completed');
        } catch (\Exception $e) {
            Log::error('Error checking makeup certifications', ['error' => $e->getMessage()]);
        }

        try {
            $browCertificationService->checkExpiringCertificates();
            Log::info('Brow certifications check completed');
        } catch (\Exception $e) {
            Log::error('Error checking brow certifications', ['error' => $e->getMessage()]);
        }

        try {
            $lashCertificationService->checkExpiringCertificates();
            Log::info('Lash certifications check completed');
        } catch (\Exception $e) {
            Log::error('Error checking lash certifications', ['error' => $e->getMessage()]);
        }

        Log::info('Expiring certifications check completed for all verticals');
    }
}
