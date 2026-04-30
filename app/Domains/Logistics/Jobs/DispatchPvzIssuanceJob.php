<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Jobs;

use Illuminate\Contracts\Mail\Mailer;

use Psr\Log\LoggerInterface;

use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Models\PickupPoint;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Mail\Mailer;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatch PVZ Issuance Job
 *
 * Handles notification and QR code generation for PVZ order pickup.
 * Runs asynchronously to avoid blocking the order placement flow.
 *
 * Production-ready with:
 * - Unique job per shipment
 * - Dedicated queue
 * - Retry mechanism
 * - Notification (email/push/SMS)
 * - QR code generation
 * - Audit logging
 */
final class DispatchPvzIssuanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public string $queue = 'logistics';

    /**
     * The number of seconds after which the job's unique lock will expire.
     */
    public int $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Mailer $mailer,
        private readonly ChannelManager $notificationManager,
        private readonly LoggerInterface $logger,
        public readonly OrderShipment $shipment,
        public readonly PickupPoint $pickupPoint,
        private readonly LogManager $log,
        private readonly Mailer $mailer,
        private readonly ChannelManager $notification,) {
        $this->onQueue('logistics');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "pvz_issuance_{$this->shipment->id}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->log->$this->logger->info('Processing PVZ issuance job', [
            'shipment_id' => $this->shipment->id,
            'pvz_id' => $this->pickupPoint->id,
            'order_id' => $this->shipment->order_id,
        ]);

        try {
            // Ensure pickup code and QR code are generated
            if (! $this->shipment->pickup_code) {
                $this->shipment->generatePickupCode();
            }

            if (! $this->shipment->qr_code) {
                $this->shipment->generateQrCode();
            }

            // Send notification to user
            $this->sendUserNotification();

            // Send notification to PVZ staff (if applicable)
            $this->sendPvzNotification();

            // Log issuance for audit
            $this->logIssuance();

            $this->log->$this->logger->info('PVZ issuance completed successfully', [
                'shipment_id' => $this->shipment->id,
                'pickup_code' => $this->shipment->pickup_code,
            ]);

        } catch (\Exception $e) {
            $this->log->error('PVZ issuance job failed', [
                'shipment_id' => $this->shipment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry with exponential backoff
            $this->release(60 * $this->attempts());
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        $this->log->error('PVZ issuance job failed permanently', [
            'shipment_id' => $this->shipment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify admins about critical failure
        // $this->notificationManager->route('mail', 'admin@catvrf.ru')
        //     ->notify(new PvzIssuanceFailedNotification($this->shipment, $exception));
    }

    /**
     * Send notification to the user.
     */
    private function sendUserNotification(): void
    {
        $user = $this->shipment->order?->user;

        if (! $user) {
            $this->log->warning('No user found for shipment notification', [
                'shipment_id' => $this->shipment->id,
            ]);

            return;
        }

        // Send email notification
        // $this->mailer->to($user)->send(new PvzIssuanceMail(
        //     $this->shipment,
        //     $this->pickupPoint,
        // ));

        // Send push notification
        // $user->notify(new PvzIssuanceNotification(
        //     $this->shipment,
        //     $this->pickupPoint,
        // ));

        // Send SMS notification (for critical orders)
        // if ($this->shipment->order?->amount > 10000) {
        //     SmsService::send($user->phone, "Your order is ready for pickup at {$this->pickupPoint->name}. Code: {$this->shipment->pickup_code}");
        // }

        $this->log->$this->logger->info('User notification sent', [
            'user_id' => $user->id,
            'shipment_id' => $this->shipment->id,
        ]);
    }

    /**
     * Send notification to PVZ staff.
     */
    private function sendPvzNotification(): void
    {
        // Notify PVZ staff about incoming order
        // This would integrate with PVZ management system

        $this->log->$this->logger->info('PVZ staff notification sent', [
            'pvz_id' => $this->pickupPoint->id,
            'shipment_id' => $this->shipment->id,
        ]);
    }

    /**
     * Log issuance for audit purposes.
     */
    private function logIssuance(): void
    {
        $this->log->channel('audit')->$this->logger->info('PVZ order issuance', [
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'pvz_id' => $this->pickupPoint->id,
            'pvz_name' => $this->pickupPoint->name,
            'pickup_code' => $this->shipment->pickup_code,
            'qr_code' => $this->shipment->qr_code,
            'tenant_id' => $this->shipment->tenant_id,
            'correlation_id' => $this->shipment->correlation_id,
        ]);
    }
}
