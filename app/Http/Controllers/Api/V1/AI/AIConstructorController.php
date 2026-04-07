<?php declare(strict_types=1);

/**
 * AIConstructorController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/aiconstructorcontroller
 * @see https://catvrf.ru/docs/aiconstructorcontroller
 * @see https://catvrf.ru/docs/aiconstructorcontroller
 * @see https://catvrf.ru/docs/aiconstructorcontroller
 */


namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AIConstructorController extends Controller
{

    public function __construct(private readonly AIConstructorService $constructorService,
        private readonly ResponseFactory $response,
    )
        {

    }
        public function run(RunConstructorRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            $result = $this->constructorService->run(
                ConstructorType::from($request->validated('constructor_type')),
                $request->user(),
                $request->validated('input_parameters', []),
                $request->file('image'),
                $correlationId
            );
            if (!$result['success']) {
                return $this->response->json([
                    'message' => $result['error'],
                    'correlation_id' => $correlationId,
                ], 422);
            }
            return $this->response->json([
                'message' => 'AI Constructor finished successfully.',
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
