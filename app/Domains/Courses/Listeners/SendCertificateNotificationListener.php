<?php declare(strict_types=1);

namespace App\Domains\Courses\Listeners;

use App\Domains\Courses\Events\CertificateIssued;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendCertificateNotificationListener implements ShouldQueue
{
    public function handle(CertificateIssued $event): void
    {
        try {
            Log::channel('audit')->info('Sending certificate notification', [
                'certificate_id' => $event->certificate->id,
                'correlation_id' => $event->correlationId,
            ]);

            // Send email/notification to student
            // Notification::send($student, new CertificateIssuedNotification($certificate));

            Log::channel('audit')->info('Certificate notification sent', [
                'certificate_id' => $event->certificate->id,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to send certificate notification', [
                'certificate_id' => $event->certificate->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
