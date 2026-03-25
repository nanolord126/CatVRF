declare(strict_types=1);

<?php

namespace Modules\Analytics\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Modules\Analytics\Models\BehavioralEvent;
use Modules\Analytics\Models\CustomerSegment;
use App\Models\User;

/**
 * InterestMappingService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class InterestMappingService
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function mapUserInterests(User $user): void
    {
        $interactions = Behavioral$this->event->where('user_id', $user->id)
            ->where('event_type', 'view')
            ->pluck('payload')
            ->map(fn($p) => $p['category'] ?? $p['name'] ?? '')
            ->filter()
            ->unique()
            ->implode(', ');

        if (empty($interactions)) return;

        // Представим, что мы получаем эмбеддинги для профиля интересов
        // $result = OpenAI::embeddings()->create([
        //     'model' => 'text-embedding-3-small',
        //     'input' => $interactions,
        // ]);
        
        // $embedding = $result->embeddings[0]->embedding;

        // Сохраняем как сегмент "interest_vector"
        CustomerSegment::updateOrCreate(
            ['user_id' => $user->id, 'segment_type' => 'interest_profile'],
            [
                'value' => 'dynamic_profile',
                'metadata' => [
                    'keywords' => $interactions,
                    // 'v' => $embedding 
                ]
            ]
        );
    }
}
