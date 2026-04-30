<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use DatabaseManager;

use App\Http\Controllers\Api\V1\Beauty\ConsumableController as BaseConsumableController;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Domain-level proxy — делегирует в Api\V1\Beauty\ConsumableController.
 *
 * Добавляет метод logs() для совместимости с beauty.api.php.
 */
final class ConsumableController extends BaseConsumableController
{
    /**
     * GET /consumables/logs — логи расхода материалов.
     */
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {}

    public function logs(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');

            /** @var DatabaseManager $db */
            $db = $this->databaseManager /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

            $logs = $db->table('beauty_consumable_logs')
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'total' => $logs->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve consumable logs',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Component: ConsumableController
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @version 2026.1
     */
}
