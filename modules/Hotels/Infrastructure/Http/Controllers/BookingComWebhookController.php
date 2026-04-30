<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Application\DTOs\MarketplaceBookingDTO;
use Modules\Hotels\Application\Services\MarketplaceIntegrationService;

final class BookingComWebhookController
{
    public function __construct(
        private readonly MarketplaceIntegrationService $marketplaceIntegrationService,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            // Verify webhook signature
            $signature = $request->header('X-Booking-Signature');
            if (!$this->verifySignature($request->getContent(), $signature)) {
                Log::warning('Booking.com webhook signature verification failed');
                return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
            }

            $data = $request->json()->all();
            $event = $data['event'] ?? null;

            Log::channel('marketplace-sync')->info('Booking.com webhook received', [
                'event' => $event,
                'reservation_id' => $data['reservation']['id'] ?? null,
            ]);

            // Handle different event types
            return match ($event) {
                'reservation_created', 'reservation_modified' => $this->handleBooking($data),
                'reservation_cancelled' => $this->handleCancellation($data),
                default => response('Event not handled', Response::HTTP_OK),
            };
        } catch (\Throwable $e) {
            Log::channel('marketplace-sync')->error('Booking.com webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Error processing webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleBooking(array $data): Response
    {
        $dto = MarketplaceBookingDTO::fromBookingCom($data);
        $this->marketplaceIntegrationService->syncBookingFromMarketplace('booking_com', $dto);

        return response('Booking synced', Response::HTTP_OK);
    }

    private function handleCancellation(array $data): Response
    {
        $externalId = $data['reservation']['id'] ?? null;
        if (!$externalId) {
            return response('Missing reservation ID', Response::HTTP_BAD_REQUEST);
        }

        // Find external reference
        $reference = \Modules\Hotels\Infrastructure\Models\ExternalBookingReferenceModel::where('source', 'booking_com')
            ->where('external_id', $externalId)
            ->first();

        if (!$reference) {
            Log::warning('Booking.com cancellation for unknown booking', ['external_id' => $externalId]);
            return response('Booking not found', Response::HTTP_OK);
        }

        // Cancel booking in CRM
        $bookingService = app(\Modules\Hotels\Application\Services\BookingService::class);
        $bookingService->cancelBooking($reference->booking_id, 'Cancelled via Booking.com');

        Log::channel('marketplace-sync')->info('Booking.com cancellation processed', [
            'external_id' => $externalId,
            'booking_id' => $reference->booking_id,
        ]);

        return response('Cancellation processed', Response::HTTP_OK);
    }

    private function verifySignature(string $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $secret = config('services.booking_com.webhook_secret');
        if (!$secret) {
            return true; // Skip verification if secret not configured (dev mode)
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
