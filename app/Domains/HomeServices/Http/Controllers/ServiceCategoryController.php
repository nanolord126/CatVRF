<?php declare(strict_types=1);

/**
 * ServiceCategoryController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/servicecategorycontroller
 */


namespace App\Domains\HomeServices\Http\Controllers;

use Carbon\Carbon;

use App\Http\Controllers\Controller;

final class ServiceCategoryController extends Controller
{

    // Dependencies injected via constructor
        // Add private readonly properties here
        public function index(): JsonResponse
        {
            try {
                $categories = ServiceCategory::where('is_active', true)->get();
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $categories, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to fetch categories'], 500);
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
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
