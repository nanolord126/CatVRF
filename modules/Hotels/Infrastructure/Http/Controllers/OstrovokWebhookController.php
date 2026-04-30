<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Application\DTOs\MarketplaceBookingDTO;
use Modules\Hotels\Application\Services\MarketplaceIntegrationService;

final class OstrovokWebhookController
{
    public function __construct(
        private readonly MarketplaceIntegrationService $marketplaceIntegrationService,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            // Verify webhook signature
            $signature = $request->header('X-Ostrovok-Signature');
            if (!$this->verifySignature($request->getContent(), $signature)) {
                Log::warning('Ostrovok webhook signature verification failed');
                return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
            }

            $data = $request->json()->all();
            $event = $data['event'] ?? null;

            Log::channel('marketplace-sync')->info('Ostrovok webhook received', [
                'event' => $event,
                'order_id' => $data['order']['id'] ?? null,
            ]);

            return match ($event) {
                'order_created', 'order_modified' => $this->handleBooking($data),
                'order_cancelled' => $this->handleCancellation($data),
                default => response('Event not handled', Response::HTTP_OK),
            };
        } catch (\Throwable $e) {
            Log::channel('marketplace-sync')->error('Ostrovok webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Error processing webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleBooking(array $data): Response
    {
        $dto = MarketplaceBookingDTO::fromOstrovok($data);
        $this->marketplaceIntegrationService->syncBookingFromMarketplace('ostrovok', $dto);

        return response('Booking synced', Response::HTTP_OK);
    }

    private function handleCancellation(array $data): Response
    {
        $externalId = $data['order']['id'] ?? null;
        if (!$externalId) {
            return response('Missing order ID', Response::HTTP_BAD_REQUEST);
        }

        $reference = \Modules\Hotels\Infrastructure\Models\ExternalBookingReferenceModel::where('source', 'ostrovok')
            ->where('external_id', $externalId)
            ->first();

        if (!$reference) {
            Log::warning('Ostrovok cancellation for unknown booking', ['external_id' => $externalId]);
            return response('Booking not found', Response::HTTP_OK);
        }

        $bookingService = app(\Modules\Hotels\Application\Services\BookingService::class);
        $bookingService->cancelBooking($reference->booking_id, 'Cancelled via Ostrovok');

        Log::channel('marketplace-sync')->info('Ostrovok cancellation processed', [
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

        $secret = config('services.ostrovok.webhook_secret');
        if (!$secret) {
            return true;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
