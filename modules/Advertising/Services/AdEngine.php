<?php
namespace Modules\Advertising\Services;
use Modules\Advertising\Models\Campaign;
use Modules\Advertising\Models\Creative;
use App\Models\User;
use Bavix\Wallet\Models\Wallet;

class AdEngine {
    public function __construct(protected OrdService $ord) {}

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
