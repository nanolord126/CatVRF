<?php declare(strict_types=1);

namespace App\Services\AI\Constructors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MenuConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly RecommendationService $recommendationService)
        {
        }

        public function build(User $user, array $inputParams, ?array $imageAnalysis): array
        {
            $tasteProfile = $this->getTasteProfile($user);
            $usedTastes = [];

            $context = [
                'vertical' => 'Food',
                'sub_vertical' => 'OfficeCatering',
                'persons_count' => $inputParams['persons_count'] ?? 10,
                'cuisine_preference' => $tasteProfile['cuisines'] ?? 'european',
                'budget_per_person' => $inputParams['budget_per_person'] ?? 1500,
            ];

            if (isset($inputParams['dietary_restrictions'])) {
                $context['dietary_restrictions'] = $inputParams['dietary_restrictions'];
            }

            $recommendations = $this->recommendationService->getForUser($user->id, 'OfficeMenu', $context);

            $confidence = $this->calculateConfidence($usedTastes, $recommendations->count());

            return [
                'recommendations' => $recommendations->toArray(),
                'used_taste_profile' => $usedTastes,
                'confidence_score' => $confidence,
            ];
        }
}
