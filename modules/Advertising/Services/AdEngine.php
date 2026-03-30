<?php declare(strict_types=1);

namespace Modules\Advertising\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AdEngine extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function __construct(protected OrdService $ord) {
        /**
         * Инициализировать класс
         */
        public function __construct()
        {
            // TODO: инициализация
        }
    }
    
        public function createCampaign(User $user, array $data): Campaign {
            $campaign = Campaign::create([
                'tenant_id' => $user->tenant_id,
                'name' => $data['name'],
                'budget' => $data['budget'],
                'vertical' => $data['vertical'],
                'is_active' => true,
            ]);
    
            $user->withdraw($data['budget'], ['campaign_id' => $campaign->id]);
            return $campaign;
        }
    
        public function addCreative(Campaign $campaign, array $data): Creative {
            $creative = $campaign->creatives()->create($data);
            $creative->update(['erid' => $this->ord->getErid($creative)]);
            return $creative;
        }
}
