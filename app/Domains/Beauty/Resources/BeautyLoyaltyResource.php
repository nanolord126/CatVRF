<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BeautyLoyaltyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'points_earned' => $this['points_earned'],
            'base_points' => $this['base_points'],
            'streak_multiplier' => $this['streak_multiplier'],
            'total_points' => $this['total_points'],
            'current_streak' => $this['current_streak'],
            'tier' => $this['tier'],
            'referral_bonus' => $this['referral_bonus'],
        ];
    }
}
