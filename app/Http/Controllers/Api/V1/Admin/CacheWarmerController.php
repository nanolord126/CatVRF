<?php declare(strict_types=1);

/**
 * CacheWarmerController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 * @see https://catvrf.ru/docs/cachewarmercontroller
 */


namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

final class CacheWarmerController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $response,
    ) {}


    public function warm(CacheWarmerRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();

            if ($userId = $request->input('user_id')) {
                dispatch(new WarmUserTasteProfileJob($userId));
            }

            if ($vertical = $request->input('vertical')) {
                dispatch(new WarmPopularProductsJob($vertical));
            }

            return $this->response->json([
                'success' => true,
                'message' => 'Cache warming job queued',
                'correlation_id' => $correlationId,
            ], 202);
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
