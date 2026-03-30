<?php declare(strict_types=1);

namespace App\Services\AI\Constructors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BaseConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
