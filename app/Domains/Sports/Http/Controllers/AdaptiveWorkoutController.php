<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\DTOs\AdaptiveWorkoutPlanDto;
use App\Domains\Sports\Services\AI\SportsPersonalTrainerAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class AdaptiveWorkoutController extends Controller
{
    public function __construct(
        private SportsPersonalTrainerAIService $service,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $dto = AdaptiveWorkoutPlanDto::from($request->all());
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;
        
        $dto = new AdaptiveWorkoutPlanDto(
            userId: auth()->id(),
            tenantId: $tenantId,
            businessGroupId: $request->get('business_group_id'),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            fitnessLevel: $dto->fitnessLevel,
            goals: $dto->goals,
            limitations: $dto->limitations,
            sportType: $dto->sportType,
            weeklyFrequency: $dto->weeklyFrequency,
            sessionDurationMinutes: $dto->sessionDurationMinutes,
            availableEquipment: $dto->availableEquipment,
            idempotencyKey: $dto->idempotencyKey,
        );

        $result = $this->service->generateAdaptiveWorkoutPlan($dto);

        return response()->json($result);
    }

    public function adjust(Request $request, int $userId): JsonResponse
    {
        $this->authorize('adjust', [$this->service, $userId]);

        $feedback = $request->validate([
            'too_easy' => 'sometimes|boolean',
            'too_hard' => 'sometimes|boolean',
            'increase_intensity' => 'sometimes|boolean',
            'decrease_intensity' => 'sometimes|boolean',
            'focus_areas' => 'sometimes|array',
            'exercises_to_remove' => 'sometimes|array',
            'exercises_to_add' => 'sometimes|array',
        ]);

        $result = $this->service->adjustWorkoutPlan(
            userId: $userId,
            feedback: $feedback,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function trackProgress(Request $request, int $userId): JsonResponse
    {
        $this->authorize('track', [$this->service, $userId]);

        $sessionData = $request->validate([
            'duration_minutes' => 'required|integer|min:1|max:300',
            'exercises_completed' => 'required|integer|min:0',
            'intensity' => 'required|string|in:low,medium,high',
            'calories_burned' => 'sometimes|integer|min:0',
            'heart_rate_avg' => 'sometimes|integer|min:40|max:220',
            'notes' => 'sometimes|string|max:1000',
        ]);

        $result = $this->service->trackWorkoutProgress(
            userId: $userId,
            sessionData: $sessionData,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function show(int $userId): JsonResponse
    {
        $this->authorize('view', [$this->service, $userId]);

        $result = $this->service->getCurrentWorkoutPlan($userId);

        return response()->json([
            'workout_plan' => $result,
        ]);
    }
}
