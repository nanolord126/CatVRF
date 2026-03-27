<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;

use App\Domains\Pet\Models\PetBoardingReservation;
use App\Domains\Pet\Services\BoardingService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PetBoardingController extends Controller
{
    public function __construct(
        private readonly BoardingService $boardingService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $reservations = PetBoardingReservation::where('owner_id', auth()->id())
                ->orWhere('clinic_id', auth()->user()->clinics->pluck('id'))
                ->with(['clinic', 'owner'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $reservations,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to get reservations', ['error' => $e->getMessage()]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $reservation,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $reservation = $this->boardingService->createReservation(
                $request->validated(),
                $correlationId
            );

            Log::channel('audit')->info('Pet boarding reservation created', [
                'correlation_id' => $correlationId,
                'reservation_id' => $reservation->id ?? null,
                'tenant_id'      => $reservation->tenant_id ?? null,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $reservation,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Failed to create reservation', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reservation',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $reservation = PetBoardingReservation::findOrFail($id);
            $this->authorize('update', $reservation);

            $before = $reservation->getAttributes();

            $reservation->update([
                ...$request->validated(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Pet boarding reservation updated', [
                'correlation_id' => $correlationId,
                'reservation_id' => $reservation->id,
                'tenant_id'      => $reservation->tenant_id,
                'user_id'        => auth()->id(),
                'before'         => $before,
            ]);

            return response()->json([
                'success' => true,
                'data' => $reservation,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reservation',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $reservation = PetBoardingReservation::findOrFail($id);
            $this->authorize('cancel', $reservation);

            $reservation->delete();

            Log::channel('audit')->info('Pet boarding reservation deleted', [
                'correlation_id' => $correlationId,
                'reservation_id' => $reservation->id,
                'tenant_id'      => $reservation->tenant_id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservation deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
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

            $this->fraudControlService->check(auth()->id() ?? 0, 'boarding_cancel', 0, request()->ip(), null, $correlationId);

            $reservation = $this->boardingService->cancelReservation($reservation, $correlationId);

            Log::channel('audit')->info('Pet boarding reservation cancelled', [
                'correlation_id' => $correlationId,
                'reservation_id' => $reservation->id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $reservation,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'correlation_id' => Str::uuid(),
            ], 403);
        }
    }
}
