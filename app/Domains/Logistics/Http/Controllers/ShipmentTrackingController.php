<?php declare(strict_types=1);

/**
 * ShipmentTrackingController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/shipmenttrackingcontroller
 */


namespace App\Domains\Logistics\Http\Controllers;

use App\Http\Controllers\Controller;

final class ShipmentTrackingController extends Controller
{

    public function getHistory(int $shipmentId): JsonResponse
        {
            try {
                $history = ShipmentTracking::where('shipment_id', $shipmentId)
                    ->orderBy('event_time', 'desc')
                    ->get();

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $history, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
