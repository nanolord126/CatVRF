<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendCertificateNotificationListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
