<?php

declare(strict_types=1);

namespace Modules\Analytics\Services;

use App\Models\User;
use Modules\Analytics\Models\BehavioralEvent;
use Modules\Analytics\Models\CustomerSegment;
use Modules\Common\Services\AbstractTechnicalVerticalService;

final class InterestMappingService extends AbstractTechnicalVerticalService
{
    public function isEnabled(): bool
    {
        return $this->tenant->settings['interest_mapping_enabled'] ?? true;
    }

    public function mapUserInterests(User $user): void
        {
            $interactions = BehavioralEvent::where('user_id', $user->id)
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
