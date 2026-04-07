<?php

declare(strict_types=1);

namespace Modules\Bonuses\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Bonuses\Application\UseCases\AwardBonusUseCase;
use Modules\Bonuses\Interfaces\Http\Requests\AwardBonusRequest;
use Illuminate\Support\Facades\Log;

final class BonusController extends Controller
{
    public function __construct(private readonly AwardBonusUseCase $awardBonusUseCase)
    {
    }

    public function award(AwardBonusRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();

        try {
            $bonusData = $request->toData();
            $this->awardBonusUseCase->execute($bonusData);

            return response()->json([
                'success' => true,
                'message' => 'Bonus awarded successfully.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Bonus award failed.', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bonus award.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
