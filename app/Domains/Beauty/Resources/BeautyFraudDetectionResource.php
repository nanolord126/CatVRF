<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BeautyFraudDetectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'fraud_score' => $this['fraud_score'],
            'ml_score' => $this['ml_score'],
            'rule_score' => $this['rule_score'],
            'behavior_score' => $this['behavior_score'],
            'risk_level' => $this['risk_level'],
            'action_required' => $this['action_required'],
            'flags' => $this['flags'],
        ];
    }
}
