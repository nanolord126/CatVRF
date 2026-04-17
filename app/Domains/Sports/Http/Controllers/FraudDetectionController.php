<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Services\SportsFraudDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class FraudDetectionController extends Controller
{
    public function __construct(
        private SportsFraudDetectionService $service,
    ) {}

    public function detectCancellationFraud(Request $request, int $bookingId): JsonResponse
    {
        $this->authorize('detectFraud', $bookingId);

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        $result = $this->service->detectCancellationFraud(
            userId: auth()->id(),
            bookingId: $bookingId,
            cancellationReason: $validated['cancellation_reason'],
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function detectNoShowFraud(Request $request, int $bookingId): JsonResponse
    {
        $this->authorize('detectFraud', $bookingId);

        $result = $this->service->detectNoShowFraud(
            userId: auth()->id(),
            bookingId: $bookingId,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function detectBookingPatternFraud(Request $request, int $userId): JsonResponse
    {
        $this->authorize('detectFraud', $userId);

        $validated = $request->validate([
            'frequency' => 'required|integer|min:0',
            'intervals' => 'sometimes|array',
            'venue_switching' => 'sometimes|boolean',
            'trainer_switching' => 'sometimes|boolean',
            'time_preferences' => 'sometimes|array',
        ]);

        $result = $this->service->detectBookingPatternFraud(
            userId: $userId,
            bookingPattern: $validated,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function applyFraudPenalty(Request $request, int $userId): JsonResponse
    {
        $this->authorize('applyPenalty', $userId);

        $validated = $request->validate([
            'fraud_type' => 'required|string|in:cancellation_fraud,no_show_fraud,booking_pattern_fraud',
            'risk_score' => 'required|numeric|min:0|max:1',
        ]);

        $this->service->applyFraudPenalty(
            userId: $userId,
            fraudType: $validated['fraud_type'],
            riskScore: floatval($validated['risk_score']),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'success' => true,
            'message' => 'Fraud penalty applied successfully',
        ]);
    }

    public function getUserFraudScore(Request $request, int $userId): JsonResponse
    {
        $this->authorize('viewFraudScore', $userId);

        $result = $this->service->getUserFraudScore(
            userId: $userId,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }
}
