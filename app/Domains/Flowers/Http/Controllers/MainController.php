<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Services\FlowerService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * MainController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary
 * @see https://catvrf.ru/docs/maincontroller
 */
final readonly class MainController extends Controller
{
    public function __construct(
        private FlowerService $flowerService,
        private FraudControlService $fraud,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            $this->fraud->check(
                userId: $request->user()?->id ?? 0,
                operationType: 'index_flowers',
                amount: 0,
                correlationId: $correlationId,
            );

            $items = $this->flowerService->getItems(['is_b2b' => $isB2B]);

            return new JsonResponse([
                'success' => true,
                'data' => $items,
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
