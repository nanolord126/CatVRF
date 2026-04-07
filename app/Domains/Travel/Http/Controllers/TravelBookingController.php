<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TravelBookingController extends Controller
{

    public function __construct(private readonly BookingService $bookingService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'booking_store', amount: 0, correlationId: $correlationId ?? '');

            try {
                $request->validate([
                    'tour_id' => 'required|exists:travel_tours,id',
                    'participants_count' => 'required|integer|min:1',
                    'participants_data' => 'nullable|array',
                ]);

                $validated = $request->all();
                $booking = $this->db->transaction(function () use ($validated, $correlationId) {
                    $tour = \App\Domains\Travel\Models\TravelTour::findOrFail(($validated['tour_id'] ?? null));

                    return $this->bookingService->createBooking(
                        $tour,
                        $request->user(),
                        ($validated['participants_count'] ?? null),
                        ($validated['participants_data'] ?? []),
                        $correlationId,
                    );
                });

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                $this->logger->error('Booking creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('view', $booking);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Booking not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'booking_update', amount: 0, correlationId: $correlationId ?? '');

            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('update', $booking);

                $validated = $request->all();
                $booking = $this->db->transaction(function () use ($validated, $booking, $correlationId) {
                    $booking->update([
                        'participants_count' => ($validated['participants_count'] ?? $booking->participants_count),
                        'participants_data' => ($validated['participants_data'] ?? $booking->participants_data),
                        'correlation_id' => $correlationId,
                    ]);

                    return $booking;
                });

                $this->logger->info('Booking updated', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'booking_destroy', amount: 0, correlationId: $correlationId ?? '');

            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('delete', $booking);

                $this->db->transaction(function () use ($booking, $correlationId) {
                    $booking->delete();
                });

                $this->logger->info('Booking deleted', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function userBookings(): JsonResponse
        {
            try {
                $bookings = TravelBooking::where('user_id', $request->user()?->id)
                    ->where('tenant_id', tenant()->id)
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $bookings->items(),
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to get bookings',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function complete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('update', $booking);

                $booking = $this->bookingService->completeBooking($booking, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to complete booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function cancel(Request $request, int $id): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());

            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('update', $booking);

                $booking = $this->bookingService->cancelBooking(
                    $booking,
                    $request->get('reason'),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to cancel booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
