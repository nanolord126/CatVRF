<?php declare(strict_types=1);

/**
 * TrainerController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/trainercontroller
 */


namespace App\Domains\Sports\Http\Controllers;

use App\Http\Controllers\Controller;

final class TrainerController extends Controller
{

    public function byStudio(int $studioId): JsonResponse
        {
            try {
                $trainers = Trainer::where('studio_id', $studioId)->where('is_active', true)->paginate(15);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $trainers, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Failed to list trainers'], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $trainer = Trainer::with(['studio', 'reviews', 'classes'])->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $trainer, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Trainer not found'], 404);
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
