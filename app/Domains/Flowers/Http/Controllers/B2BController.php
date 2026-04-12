<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Services\B2BService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
final class B2BController extends Controller
{
    public function __construct(
        private B2BService $b2bService,
        private FraudControlService $fraud,
    ) {}

    public function createStorefront(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            if (!$isB2B) {
                return new JsonResponse([
                    'error' => 'B2B only',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $this->fraud->check(
                userId: $request->user()?->id ?? 0,
                operationType: 'mutation',
                amount: 0,
                correlationId: $correlationId,
            );

            $storefront = $this->b2bService->createStorefront($request->all(), $correlationId);

            return new JsonResponse([
                'success' => true,
                'data' => $storefront,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 403);
        }
    }
}
