<?php

declare(strict_types=1);

namespace Modules\Hotels\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Application\DTOs\MarketplaceBookingDTO;
use Modules\Hotels\Application\Services\MarketplaceIntegrationService;

final class AirbnbWebhookController
{
    public function __construct(
        private readonly MarketplaceIntegrationService $marketplaceIntegrationService,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            // Verify OAuth signature
            $authorization = $request->header('Authorization');
            if (!$this->verifyAuthorization($authorization)) {
                Log::warning('Airbnb webhook authorization failed');
                return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
            }

            $data = $request->json()->all();
            $event = $data['event'] ?? null;

            Log::channel('marketplace-sync')->info('Airbnb webhook received', [
                'event' => $event,
                'reservation_code' => $data['reservation']['code'] ?? null,
            ]);

            return match ($event) {
                'reservation_created', 'reservation_updated' => $this->handleBooking($data),
                'reservation_cancelled' => $this->handleCancellation($data),
                default => response('Event not handled', Response::HTTP_OK),
            };
        } catch (\Throwable $e) {
            Log::channel('marketplace-sync')->error('Airbnb webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Error processing webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleBooking(array $data): Response
    {
        $dto = MarketplaceBookingDTO::fromAirbnb($data);
        $this->marketplaceIntegrationService->syncBookingFromMarketplace('airbnb', $dto);

        return response('Booking synced', Response::HTTP_OK);
    }

    private function handleCancellation(array $data): Response
    {
        $externalId = $data['reservation']['code'] ?? null;
        if (!$externalId) {
            return response('Missing reservation code', Response::HTTP_BAD_REQUEST);
        }

        $reference = \Modules\Hotels\Infrastructure\Models\ExternalBookingReferenceModel::where('source', 'airbnb')
            ->where('external_id', $externalId)
            ->first();

        if (!$reference) {
            Log::warning('Airbnb cancellation for unknown booking', ['external_id' => $externalId]);
            return response('Booking not found', Response::HTTP_OK);
        }

        $bookingService = app(\Modules\Hotels\Application\Services\BookingService::class);
        $bookingService->cancelBooking($reference->booking_id, 'Cancelled via Airbnb');

        Log::channel('marketplace-sync')->info('Airbnb cancellation processed', [
            'external_id' => $externalId,
            'booking_id' => $reference->booking_id,
        ]);

        return response('Cancellation processed', Response::HTTP_OK);
    }

    private function verifyAuthorization(?string $authorization): bool
    {
        if (!$authorization) {
            return false;
        }

        $token = config('services.airbnb.webhook_token');
        if (!$token) {
            return true;
        }

        return str_starts_with($authorization, 'Bearer ' . $token);
    }
}
