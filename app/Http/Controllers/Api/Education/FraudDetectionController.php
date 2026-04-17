<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Services\EducationFraudDetectionService;
use App\Domains\Education\Events\FraudDetectedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;

final readonly class FraudDetectionController extends Controller
{
    public function __construct(
        private EducationFraudDetectionService $fraudService,
    ) {}

    public function detectCheating(int $enrollmentId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');

        $detection = $this->fraudService->detectCheating($enrollmentId, $userId, $correlationId);

        if ($detection->isCheating) {
            Event::dispatch(new FraudDetectedEvent(
                fraudId: $detection->detectionId,
                fraudType: 'cheating',
                severity: $detection->severity,
                userId: $userId,
                enrollmentId: $enrollmentId,
                reviewId: null,
                tenantId: $detection->tenantId,
                correlationId: $correlationId,
            ));
        }

        return response()->json($detection->toArray())
            ->header('X-Correlation-ID', $correlationId);
    }

    public function detectReviewFraud(int $reviewId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'review_content' => ['required', 'string'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $userId = (int) $request->input('user_id');
        $reviewContent = $request->input('review_content');

        $detection = $this->fraudService->detectReviewFraud($reviewId, $userId, $reviewContent, $correlationId);

        if ($detection->isFraudulent) {
            Event::dispatch(new FraudDetectedEvent(
                fraudId: $detection->detectionId,
                fraudType: 'fake_review',
                severity: $detection->severity,
                userId: $userId,
                enrollmentId: null,
                reviewId: $reviewId,
                tenantId: $detection->tenantId,
                correlationId: $correlationId,
            ));
        }

        return response()->json($detection->toArray())
            ->header('X-Correlation-ID', $correlationId);
    }
}
