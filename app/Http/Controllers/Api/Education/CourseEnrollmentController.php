<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Domains\Education\Services\CourseEnrollmentService;
use App\Domains\Education\Services\CRMIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CourseEnrollmentController extends Controller
{
    public function __construct(
        private CourseEnrollmentService $enrollmentService,
        private CRMIntegrationService $crmService,
    ) {}

    public function enroll(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'corporate_contract_id' => ['nullable', 'integer'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $idempotencyKey = $request->header('X-Idempotency-Key');

        $userId = (int) $request->input('user_id');
        $courseId = (int) $request->input('course_id');
        $corporateContractId = $request->input('corporate_contract_id') ? (int) $request->input('corporate_contract_id') : null;

        $result = $this->enrollmentService->enrollWithSplitPayment(
            $userId,
            $courseId,
            $corporateContractId,
            $correlationId,
            $idempotencyKey,
        );

        $this->crmService->syncEnrollmentCreated($result['enrollment_id'], $result, $correlationId);

        return response()->json($result)
            ->setStatusCode(201)
            ->header('X-Correlation-ID', $correlationId);
    }

    public function updateProgress(int $enrollmentId, Request $request): JsonResponse
    {
        $request->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $progressPercent = (int) $request->input('progress_percent');

        $this->enrollmentService->updateProgress($enrollmentId, $progressPercent, $correlationId);

        $this->crmService->syncProgressUpdated($enrollmentId, $progressPercent, $correlationId);

        return response()->json([
            'message' => 'Progress updated',
            'enrollment_id' => $enrollmentId,
            'progress_percent' => $progressPercent,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }

    public function cancelEnrollment(int $enrollmentId, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $reason = $request->input('reason');

        $this->enrollmentService->cancelEnrollment($enrollmentId, $reason, $correlationId);

        $this->crmService->syncEnrollmentCancelled($enrollmentId, $reason, $correlationId);

        return response()->json([
            'message' => 'Enrollment cancelled',
            'enrollment_id' => $enrollmentId,
        ])
            ->header('X-Correlation-ID', $correlationId);
    }

    public function issueCertificate(int $enrollmentId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid();

        $certificate = $this->enrollmentService->issueCertificate($enrollmentId, $correlationId);

        $this->crmService->syncCertificateIssued($enrollmentId, $certificate, $correlationId);

        return response()->json($certificate)
            ->header('X-Correlation-ID', $correlationId);
    }
}
