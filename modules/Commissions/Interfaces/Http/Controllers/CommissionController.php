<?php

declare(strict_types=1);

namespace Modules\Commissions\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Commissions\Application\UseCases\CalculateCommissionUseCase;
use Modules\Commissions\Interfaces\Http\Requests\CalculateCommissionRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CommissionController extends Controller
{
    public function __construct(private readonly CalculateCommissionUseCase $calculateCommissionUseCase)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/commissions/calculate",
     *     summary="Calculate commission for a transaction",
     *     tags={"Commissions"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CalculateCommissionRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commission calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/CommissionCalculationResult")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function calculate(CalculateCommissionRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $commissionData = $request->toData($correlationId);
            $result = $this->calculateCommissionUseCase->execute($commissionData);

            return response()->json([
                'success' => true,
                'message' => 'Commission calculated successfully.',
                'data' => $result->toArray(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            Log::channel('audit')->warning('Invalid argument for commission calculation.', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'request_data' => $request->validated(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Commission calculation failed.', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during commission calculation.',
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
