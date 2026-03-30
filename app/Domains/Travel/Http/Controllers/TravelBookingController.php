<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelBookingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly BookingService $bookingService,
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraudControlService->check(auth()->id() ?? 0, 'booking_store', 0, $request->ip(), null, $correlationId);

            try {
                $request->validate([
                    'tour_id' => 'required|exists:travel_tours,id',
                    'participants_count' => 'required|integer|min:1',
                    'participants_data' => 'nullable|array',
                ]);

                $validated = $request->all();
                $booking = DB::transaction(function () use ($validated, $correlationId) {
                    $tour = \App\Domains\Travel\Models\TravelTour::findOrFail(($validated['tour_id'] ?? null));

                    return $this->bookingService->createBooking(
                        $tour,
                        auth()->user(),
                        ($validated['participants_count'] ?? null),
                        ($validated['participants_data'] ?? []),
                        $correlationId,
                    );
                });

                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Booking creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
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

                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = $request->get('correlation_id', Str::uuid()->toString());
            $this->fraudControlService->check(auth()->id() ?? 0, 'booking_update', 0, $request->ip(), null, $correlationId);

            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('update', $booking);

                $validated = $request->all();
                $booking = DB::transaction(function () use ($validated, $booking, $correlationId) {
                    $booking->update([
                        'participants_count' => ($validated['participants_count'] ?? $booking->participants_count),
                        'participants_data' => ($validated['participants_data'] ?? $booking->participants_data),
                        'correlation_id' => $correlationId,
                    ]);

                    return $booking;
                });

                Log::channel('audit')->info('Booking updated', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'booking_destroy', 0, request()->ip(), null, $correlationId);

            try {
                $booking = TravelBooking::where('tenant_id', tenant()->id)->findOrFail($id);

                $this->authorize('delete', $booking);

                DB::transaction(function () use ($booking, $correlationId) {
                    $booking->delete();
                });

                Log::channel('audit')->info('Booking deleted', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function userBookings(): JsonResponse
        {
            try {
                $bookings = TravelBooking::where('user_id', auth()->id())
                    ->where('tenant_id', tenant()->id)
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $bookings->items(),
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (Throwable $e) {
                return response()->json([
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

                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return response()->json([
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

                return response()->json([
                    'success' => true,
                    'data' => $booking,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cancel booking',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
