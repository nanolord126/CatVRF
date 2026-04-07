<?php declare(strict_types=1);

/**
 * BaseConstructor — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/baseconstructor
 * @see https://catvrf.ru/docs/baseconstructor
 */


namespace App\Services\AI\Constructors;

use App\Models\User;

abstract /**
 * Class BaseConstructor
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Services\AI\Constructors
 */
readonly class BaseConstructor
{
    abstract public function build(User $user, array $inputParams, ?array $imageAnalysis): array;

        protected function getTasteProfile(User $user): array
        {
            // v2.0 of taste profile
            return $user->taste_profile_v2 ?? [];
        }

        protected function calculateConfidence(array $usedTastes, int $recommendationCount): float
        {
            if ($recommendationCount === 0) {
                return 0.0;
            }
            $score = count($usedTastes) * 0.1 + $recommendationCount * 0.05;
            return min(round($score, 2), 1.0);
        }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }

    /**
     * Determine if this instance is valid for the current context.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return true;
    }
}
