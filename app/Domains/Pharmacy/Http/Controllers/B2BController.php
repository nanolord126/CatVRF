<?php declare(strict_types=1);

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


namespace App\Domains\Pharmacy\Http\Controllers;

use App\Http\Controllers\Controller;

final class B2BController extends Controller
{

    public function __construct(private readonly B2BService $service) {}

        public function store(Request $request): JsonResponse
        {
            $cid = (string) Str::uuid();
            try {
                $order = $this->service->placeOrder($request->all(), $cid);
                return new \Illuminate\Http\JsonResponse(['order' => $order, 'correlation_id' => $cid]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['error' => $e->getMessage()], 500);
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
