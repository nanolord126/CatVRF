<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PetBoardingController extends Controller
{

    public function __construct(
            private readonly BoardingService $boardingService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $reservations = PetBoardingReservation::where('owner_id', $request->user()?->id)
                    ->orWhere('clinic_id', $request->user()->clinics->pluck('id'))
                    ->with(['clinic', 'owner'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reservations,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get reservations', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve reservations',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show($id): JsonResponse
        {
            try {
                $reservation = PetBoardingReservation::with(['clinic', 'owner'])
                    ->findOrFail($id);

                $this->authorize('view', $reservation);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reservation,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Reservation not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $reservation = $this->boardingService->createReservation(
                    $request->validated(),
                    $correlationId
                );

                $this->logger->info('Pet boarding reservation created', [
                    'correlation_id' => $correlationId,
                    'reservation_id' => $reservation->id ?? null,
                    'tenant_id'      => $reservation->tenant_id ?? null,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reservation,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create reservation', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create reservation',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $reservation = PetBoardingReservation::findOrFail($id);
                $this->authorize('update', $reservation);

                $before = $reservation->getAttributes();

                $reservation->update([
                    ...$request->validated(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Pet boarding reservation updated', [
                    'correlation_id' => $correlationId,
                    'reservation_id' => $reservation->id,
                    'tenant_id'      => $reservation->tenant_id,
                    'user_id'        => $request->user()?->id,
                    'before'         => $before,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reservation,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update reservation',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy($id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $reservation = PetBoardingReservation::findOrFail($id);
                $this->authorize('cancel', $reservation);

                $reservation->delete();

                $this->logger->info('Pet boarding reservation deleted', [
                    'correlation_id' => $correlationId,
                    'reservation_id' => $reservation->id,
                    'tenant_id'      => $reservation->tenant_id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Reservation deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete reservation',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function cancel($id): JsonResponse
        {
            try {
                $reservation = PetBoardingReservation::findOrFail($id);
                $this->authorize('cancel', $reservation);
                $correlationId = Str::uuid()->toString();

                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'boarding_cancel', amount: 0, correlationId: $correlationId ?? '');

                $reservation = $this->boardingService->cancelReservation($reservation, $correlationId);

                $this->logger->info('Pet boarding reservation cancelled', [
                    'correlation_id' => $correlationId,
                    'reservation_id' => $reservation->id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reservation,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to cancel reservation',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function analyticsAdmin(): JsonResponse
        {
            try {
                $analytics = [
                    'total_reservations' => PetBoardingReservation::count(),
                    'active' => PetBoardingReservation::where('status', 'active')->count(),
                    'completed' => PetBoardingReservation::where('status', 'completed')->count(),
                    'avg_commission' => PetBoardingReservation::where('payment_status', 'paid')->avg('commission_amount'),
                ];

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => Str::uuid(),
                ], 403);
            }
        }
}
