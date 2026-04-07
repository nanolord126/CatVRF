<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class BookingController extends Controller
{

    public function __construct(private readonly BookingService $bookingService,
            private readonly TicketingService $ticketingService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function store(): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($correlationId) {
                    $booking = $this->bookingService->createBooking(
                        $request->input('venue_id'),
                        $request->input('event_schedule_id'),
                        $request->user()?->id,
                        $request->input('number_of_seats'),
                        $correlationId,
                    );

                    $this->ticketingService->generateTickets($booking, $correlationId);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create booking', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function myBookings(): JsonResponse
        {
            try {
                $bookings = Booking::where('customer_id', $request->user()?->id)
                    ->with('venue', 'eventSchedule')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $bookings, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $this->authorize('view', $booking);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $booking->load('venue', 'eventSchedule', 'tickets'), 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $booking = Booking::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($booking, $correlationId) {
                    $booking->update(['correlation_id' => $correlationId]);
                    $this->logger->info('Booking updated', ['booking_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $booking, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->authorize('cancel', $booking);

                $this->db->transaction(function () use ($booking, $correlationId) {
                    $this->bookingService->cancelBooking($booking, $request->input('reason'), $correlationId);
                    $this->ticketingService->refundTickets($booking, $correlationId);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function confirm(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($booking, $correlationId) {
                    $booking->update(['status' => 'confirmed', 'correlation_id' => $correlationId]);
                    $this->logger->info('Booking confirmed', ['booking_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $booking, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function expire(int $id): JsonResponse
        {
            try {
                $booking = Booking::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($booking, $correlationId) {
                    $booking->update(['status' => 'completed', 'correlation_id' => $correlationId]);
                    $this->logger->info('Booking completed', ['booking_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
