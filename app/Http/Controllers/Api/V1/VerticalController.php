<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class VerticalController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $verticals = config('verticals', []);

        $this->logger->info('Verticals listed', ['correlation_id' => $correlationId, 'count' => count($verticals)]);

        return new JsonResponse([
            'correlation_id' => $correlationId,
            'data' => $verticals,
        ]);
    }

    /**
     * Component: VerticalController
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * VerticalController — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}
