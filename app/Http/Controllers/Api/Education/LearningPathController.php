<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Requests\CreateLearningPathRequest;
use App\Domains\Education\DTOs\CreateLearningPathDto;
use App\Domains\Education\Services\AI\EducationLearningPathAIConstructorService;
use App\Domains\Education\Resources\LearningPathResource;
use App\Domains\Education\Events\LearningPathGeneratedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;

final readonly class LearningPathController extends Controller
{
    public function __construct(
        private EducationLearningPathAIConstructorService $aiConstructor,
    ) {}

    public function generate(CreateLearningPathRequest $request): JsonResponse
    {
        $dto = CreateLearningPathDto::from($request);

        $recommendation = $this->aiConstructor->generatePersonalizedLearningPath($dto);

        Event::dispatch(new LearningPathGeneratedEvent(
            userId: $dto->userId,
            courseId: $dto->courseId,
            tenantId: $dto->tenantId,
            businessGroupId: $dto->businessGroupId,
            recommendation: $recommendation,
            correlationId: $dto->correlationId,
        ));

        return (new LearningPathResource($recommendation))
            ->response()
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $dto->correlationId);
    }

    public function adapt(int $enrollmentId, \Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'progress_data' => ['required', 'array'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();

        $recommendation = $this->aiConstructor->adaptLearningPath(
            enrollmentId: $enrollmentId,
            progressData: $request->input('progress_data'),
            correlationId: $correlationId,
        );

        return (new LearningPathResource($recommendation))
            ->response()
            ->header('X-Correlation-ID', $correlationId);
    }
}
