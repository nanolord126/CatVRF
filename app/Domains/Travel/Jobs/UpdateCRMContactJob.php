<?php declare(strict_types=1);

namespace App\Domains\Travel\Jobs;

use App\Domains\Travel\Models\TourBooking;
use App\Services\CRM\CRMIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Update CRM Contact Job
 * 
 * Syncs booking status changes to CRM system (HubSpot, Salesforce, AmoCRM, or custom).
 * Ensures CRM has up-to-date information at every stage: booking, check-in, review.
 */
final readonly class UpdateCRMContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $bookingId,
        public readonly string $status,
        public readonly string $correlationId,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(CRMIntegrationService $crm): void
    {
        $booking = TourBooking::with(['user', 'tour'])->findOrFail($this->bookingId);

        $crmData = [
            'contact_id' => $booking->user->crm_contact_id ?? null,
            'email' => $booking->user->email,
            'phone' => $booking->user->phone,
            'first_name' => $booking->user->first_name,
            'last_name' => $booking->user->last_name,
            'booking_status' => $this->status,
            'booking_uuid' => $booking->uuid,
            'tour_name' => $booking->tour->title ?? 'Unknown',
            'tour_destination' => $booking->tour->destination->name ?? 'Unknown',
            'start_date' => $booking->start_date,
            'end_date' => $booking->end_date,
            'person_count' => $booking->person_count,
            'total_amount' => $booking->total_amount,
            'is_b2b' => $booking->business_group_id !== null,
            'correlation_id' => $this->correlationId,
        ];

        try {
            $crmContactId = $crm->updateOrCreateContact($crmData);

            if ($crmContactId && !$booking->user->crm_contact_id) {
                $booking->user->update(['crm_contact_id' => $crmContactId]);
            }

            $this->logger->info('CRM contact updated successfully', [
                'booking_id' => $this->bookingId,
                'status' => $this->status,
                'crm_contact_id' => $crmContactId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CRM contact update failed', [
                'booking_id' => $this->bookingId,
                'status' => $this->status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $this->correlationId,
            ]);

            $this->release(60);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('CRM update job failed', [
            'booking_id' => $this->bookingId,
            'status' => $this->status,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
