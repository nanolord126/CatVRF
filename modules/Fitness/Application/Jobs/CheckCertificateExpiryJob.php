<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Fitness\Application\Services\TrainerCertificationService;

final readonly class CheckCertificateExpiryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct() {}

    public function handle(TrainerCertificationService $service): void
    {
        $results = $service->checkExpiringCertificates();

        // Notify about expiring certificates
        foreach ($results['expiring_soon'] as $certification) {
            $this->notifyExpiringSoon($certification);
        }

        // Handle expired certificates
        foreach ($results['expired'] as $certification) {
            $this->handleExpired($certification);
        }

        Log::info('Certificate expiry check completed', [
            'expiring_soon' => count($results['expiring_soon']),
            'expired' => count($results['expired']),
        ]);
    }

    private function notifyExpiringSoon(object $certification): void
    {
        $daysUntilExpiry = $certification->expiryDate
            ? Carbon\CarbonImmutable::now()->diffInDays($certification->expiryDate)
            : 0;

        Log::info('Certificate expiring soon notification', [
            'trainer_id' => $certification->trainerId,
            'certification' => $certification->name,
            'days_until_expiry' => $daysUntilExpiry,
        ]);

        // Send notification to trainer and manager
        // This would integrate with your notification system
        // Could send email, SMS, or in-app notification
    }

    private function handleExpired(object $certification): void
    {
        Log::warning('Certificate expired', [
            'trainer_id' => $certification->trainerId,
            'certification' => $certification->name,
            'expiry_date' => $certification->expiryDate,
        ]);

        // Notify trainer and manager
        // Create task for renewal
        // Block trainer from relevant sessions if critical
    }
}
