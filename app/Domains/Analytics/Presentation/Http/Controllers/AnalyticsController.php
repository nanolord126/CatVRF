<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Presentation\Http\Controllers;

use App\Domains\Analytics\Application\UseCases\TrackEventUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;

/**
 * Class AnalyticsController
 *
 * Part of the Analytics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * API Controller following CatVRF canon:
 * - Constructor injection for all dependencies
 * - Request validation via Form Requests
 * - Response via ResponseFactory DI
 * - correlation_id in all responses
 *
 * @see \App\Http\Controllers\BaseApiController
 * @package App\Domains\Analytics\Presentation\Http\Controllers
 */
final readonly class AnalyticsController extends Controller
{
    public function __construct(
        private readonly TrackEventUseCase $trackEventUseCase,
        private readonly ValidationFactory $validator,
    ) {}

    public function track(Request $request): JsonResponse
    {
        $validator = $this->validator->make($request->all(), [
            'event_type' => 'required|string|max:255',
            'payload' => 'sometimes|array',
            'vertical' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return new \Illuminate\Http\JsonResponse(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $user = $request->user();
        $tenant = filament()->getTenant();
        $correlationId = $request->header('X-Correlation-ID');

        try {
            $this->trackEventUseCase->execute(
                $tenant->id,
                $user?->id,
                $validated['event_type'],
                $validated['payload'] ?? [],
                $validated['vertical'],
                $request->ip(),
                $request->fingerprint(), // Assuming a middleware adds this
                $correlationId
            );

            return new \Illuminate\Http\JsonResponse(['message' => 'Event tracked'], 202);

        } catch (\Throwable $e) {
            return new \Illuminate\Http\JsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}
