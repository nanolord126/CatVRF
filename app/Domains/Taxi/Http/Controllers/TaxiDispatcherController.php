<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiDispatcherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class TaxiDispatcherController extends Controller
{
    public function __construct(
        private readonly TaxiDispatcherService $dispatcherService,
    ) {}

    public function assignDriver(Request $request, int $rideId): JsonResponse
    {
        $result = $this->dispatcherService->assignDriverToRide(
            rideId: $rideId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    public function acceptAssignment(Request $request, int $queueId, int $driverId): JsonResponse
    {
        $queueEntry = $this->dispatcherService->acceptRideAssignment(
            queueId: $queueId,
            driverId: $driverId,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'queue_entry' => $queueEntry,
        ]);
    }

    public function declineAssignment(Request $request, int $queueId, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $queueEntry = $this->dispatcherService->declineRideAssignment(
            queueId: $queueId,
            driverId: $driverId,
            reason: $validated['reason'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'queue_entry' => $queueEntry,
        ]);
    }

    public function processTimeouts(Request $request): JsonResponse
    {
        $processed = $this->dispatcherService->processTimeoutAssignments(
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'processed' => $processed,
        ]);
    }

    public function getDashboard(Request $request): JsonResponse
    {
        $dashboard = $this->dispatcherService->getDispatcherDashboard(
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'dashboard' => $dashboard,
        ]);
    }
}
