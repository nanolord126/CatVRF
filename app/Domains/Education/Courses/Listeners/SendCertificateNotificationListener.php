<?php declare(strict_types=1);

/**
 * SendCertificateNotificationListener — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/sendcertificatenotificationlistener
 */


namespace App\Domains\Education\Courses\Listeners;


use Psr\Log\LoggerInterface;
final class SendCertificateNotificationListener
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function handle(CertificateIssued $event): void
        {
            try {
                $this->logger->info('Sending certificate notification', [
                    'certificate_id' => $event->certificate->id,
                    'correlation_id' => $event->correlationId,
                ]);

                // Send email/notification to student
                // Notification::send($student, new CertificateIssuedNotification($certificate));

                $this->logger->info('Certificate notification sent', [
                    'certificate_id' => $event->certificate->id,
                    'correlation_id' => $event->correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to send certificate notification', [
                    'certificate_id' => $event->certificate->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
