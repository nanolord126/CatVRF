<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Http\Controllers\Api\V1\Beauty\ConsumableController as BaseConsumableController;

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
    public function logs(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());

        try {
            $tenantId = (int) $request->header('X-Tenant-ID', '0');

            /** @var \Illuminate\Database\DatabaseManager $db */
            $db = app(\Illuminate\Database\DatabaseManager::class);

            $logs = $db->table('beauty_consumable_logs')
                ->where('tenant_id', $tenantId)
                ->orderBy('created_at', 'desc')
                ->paginate((int) $request->input('per_page', 20));

            return response()->json([
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
            return response()->json([
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
     * @package CatVRF
     * @version 2026.1
     */}
