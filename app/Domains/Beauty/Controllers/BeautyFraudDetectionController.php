<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Controllers;

use App\Domains\Beauty\DTOs\BeautyFraudDetectionDto;
use App\Domains\Beauty\Requests\BeautyFraudDetectionRequest;
use App\Domains\Beauty\Resources\BeautyFraudDetectionResource;
use App\Domains\Beauty\Services\BeautyFraudDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BeautyFraudDetectionController
{
    public function __construct(
        private BeautyFraudDetectionService $fraudDetectionService,
    ) {}

    public function analyze(BeautyFraudDetectionRequest $request): JsonResponse
    {
        $dto = BeautyFraudDetectionDto::from($request);

        $result = $this->fraudDetectionService->analyze($dto);

        return response()->json([
            'success' => true,
            'data' => new BeautyFraudDetectionResource($result),
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    public function addSuspiciousIP(Request $request): JsonResponse
    {
        $ip = $request->input('ip_address');

        if (!$ip) {
            return response()->json([
                'success' => false,
                'error' => 'IP address is required',
            ], 422);
        }

        $this->fraudDetectionService->addSuspiciousIP($ip);

        return response()->json([
            'success' => true,
            'message' => 'IP added to suspicious list',
        ]);
    }

    public function recordFailedPayment(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id');

        $this->fraudDetectionService->recordFailedPayment($userId);

        return response()->json([
            'success' => true,
            'message' => 'Failed payment recorded',
        ]);
    }
}
