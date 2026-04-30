<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\AMLScreeningService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class AMLScreeningController extends Controller
{
    public function __construct(
        private readonly AMLScreeningService $amlScreening,
    ) {}

    /**
     * Screen transaction for AML compliance
     */
    public function screenTransaction(Request $request, int $transactionId): JsonResponse
    {
        $result = $this->amlScreening->screenTransaction(
            transactionId: $transactionId,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }

    /**
     * Screen user for AML compliance
     */
    public function screenUser(Request $request, int $userId): JsonResponse
    {
        $result = $this->amlScreening->screenUser(
            userId: $userId,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return new JsonResponse($result);
    }
}
