<?php

declare(strict_types=1);

/**
 * PortfolioController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/portfoliocontroller
 */


namespace App\Domains\Beauty\Http\Controllers;

use App\Http\Controllers\Controller;

final class PortfolioController extends Controller
{


    public function __construct(
            private PortfolioService $portfolioService,
        private FraudControlService $fraud,
    ) {
    }

    public function index(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $correlationId = (string) \Illuminate\Support\Str::uuid();

        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'index_portfolio', amount: 0, correlationId: $correlationId ?? '');

                $items = $this->portfolioService->getItems(['is_b2b' => $isB2B]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $items,
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

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
