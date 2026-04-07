<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class BookingController extends Controller
{

    public function __construct(private BookingService $bookingService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function create(int $classId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {

                $booking = $this->db->transaction(function () use ($classId, $correlationId) {
                    return $this->bookingService->createBooking(
                        $classId,
                        $request->user()?->id,
                        null,
                        'class',
                        $request->input('price', 0),
                        false,
                        $correlationId
                    );
                });

                BookingConfirmationJob::dispatch($booking->id, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $booking, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Booking creation failed', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Booking failed'], 500);
            }
        }

        public function myBookings(): JsonResponse
        {
            try {
                $bookings = Booking::where('member_id', $request->user()?->id)
                    ->with(['class', 'trainer'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $bookings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to list bookings'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $this->authorize('view', $booking);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $booking, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Booking not found'], 404);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $this->authorize('cancel', $booking);

                $correlationId = Str::uuid()->toString();
                $this->db->transaction(fn() => $this->bookingService->cancelBooking($booking, 'User cancelled', $correlationId));

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Booking cancelled', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to cancel booking'], 500);
            }
        }

        public function markAttended(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $correlationId = Str::uuid()->toString();
                $this->bookingService->markAsAttended($booking, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Booking marked as attended', 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to mark attendance'], 500);
            }
        }
}
