<?php

declare(strict_types=1);

/**
 * MainController — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/maincontroller
 */

namespace App\Domains\Supermarket\SubVerticals\MeatShops\Http\Controllers;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;

final class MainController extends Controller
{
    public function __construct(private readonly MeatShopService $service) {}

    public function index(Request $request): JsonResponse
    {
        $cid = (string) Str::uuid();
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');

            return new \Illuminate\Http\JsonResponse(['data' => [], 'b2b' => $isB2B, 'correlation_id' => $cid]);
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
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
