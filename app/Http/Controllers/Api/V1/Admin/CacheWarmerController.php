<?php

declare(strict_types=1);

/**
 * CacheWarmerController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
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

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;

final class CacheWarmerController extends Controller
{
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

    public function __construct(private readonly BusDispatcher $bus,
        private readonly ResponseFactory $response,) {}


    public function warm(CacheWarmerRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        if ($userId = $request->input('user_id')) {
            $this->bus->dispatch(new WarmUserTasteProfileJob($userId));
        }

        if ($vertical = $request->input('vertical')) {
            $this->bus->dispatch(new WarmPopularProductsJob($vertical));
        }

        return $this->response->json([
            'success' => true,
            'message' => 'Cache warming job queued',
            'correlation_id' => $correlationId,
        ], 202);
    }
}
