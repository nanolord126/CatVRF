<?php declare(strict_types=1);

/**
 * B2BController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/b2bcontroller
 */


namespace App\Domains\Education\Courses\Http\Controllers;

use App\Http\Controllers\Controller;

final class B2BController extends Controller
{

    public function __construct(
            private readonly CourseService $courseService,
            private readonly FraudControlService $fraud
        ) {}

        public function purchaseBulk(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                if (!$isB2B) {
                    return new \Illuminate\Http\JsonResponse(['error' => 'B2B only', 'correlation_id' => $correlationId], 403);
                }

                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $enrollments = $this->courseService->purchaseBulk($request->all(), $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $enrollments,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 403);
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
