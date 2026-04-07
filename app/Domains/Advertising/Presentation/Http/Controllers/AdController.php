<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Http\Controllers;

use App\Domains\Advertising\Application\UseCases\ShowAdUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * AdController — serves ads to users.
 *
 * Uses ShowAdUseCase to select best ad for the user.
 * Constructor injection, correlation_id tracing.
 *
 * @package App\Domains\Advertising\Presentation\Http\Controllers
 */
final class AdController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly ShowAdUseCase $showAdUseCase,
    ) {}

    /**
     * Show a targeted ad for the current user.
     *
     * Selects the best available ad based on placement zone,
     * user profile and targeting criteria.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $placementZone = $request->input('zone', 'default');
        $correlationId = $request->header(
            'X-Correlation-ID',
            Str::uuid()->toString(),
        );

        try {
            $ad = $this->showAdUseCase->execute(
                $user,
                $placementZone,
                $request->ip(),
                $request->fingerprint() ?? '',
                $correlationId,
            );

            if (!$ad) {
                return new JsonResponse(
                    ['message' => 'No ad available', 'correlation_id' => $correlationId],
                    204,
                );
            }

            return new JsonResponse(array_merge($ad, [
                'correlation_id' => $correlationId,
            ]));
        } catch (\DomainException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
