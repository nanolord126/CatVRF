<?php declare(strict_types=1);

/**
 * AutoCatalogController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/autocatalogcontroller
 * @see https://catvrf.ru/docs/autocatalogcontroller
 */


namespace App\Http\Controllers\Api\Auto;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class AutoCatalogController extends Controller
{

    public function __construct(
            private AutoPartService $partService,
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {}
        /**
         * Поиск совместимых запчастей по VIN.
         *
         * GET /api/v1/auto/catalog/search?vin={vin}
         */
        public function searchByVin(AutoVinSearchRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $vin = $request->get('vin');
            try {
                $this->logger->channel('audit')->info('VIN Search Request Received', [
                    'vin' => $vin,
                    'correlation_id' => $correlationId,
                    'user_agent' => $request->userAgent(),
                ]);
                // 1. Поиск в каталоге (Service Layer)
                $parts = $this->partService->findPartsByVin($vin, $correlationId);
                return $this->response->json([
                    'success' => true,
                    'data' => $parts,
                    'meta' => [
                        'vin' => $vin,
                        'correlation_id' => $correlationId,
                        'count' => $parts->count(),
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('VIN Search Failed', [
                    'vin' => $vin,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Произошла ошибка при поиске запчастей. Попробуйте позже.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
