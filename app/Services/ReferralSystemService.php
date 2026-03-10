<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Referral;
use App\Models\User;

/**
 * Final 2026 Referral System: Multi-tier, strict types, production-ready.
 */
final readonly class ReferralSystemService
{
    public function recordReferral(User $referrer, User $referred, string $type = 'individual'): Referral
    {
        return Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'type' => $type
        ]);
    }

    public function processMilestones(User $business): void
    {
        $referral = Referral::where('referred_id', $business->id)->first();
        if (!$referral) {
            return;
        }

        if ($business->balance >= 50000 && !$referral->bonus_paid_50k) {
            $referral->referrer->deposit(2000, ['description' => 'Business milestone referral bonus']);
            $referral->update(['bonus_paid_50k' => true]);
        }
    }

    public function processClientReferral(User $referrer, User $client, float $spent): void
    {
        if ($spent > 10000) {
            $referrer->deposit(1000, ['description' => 'Client purchase referral bonus']);
        }
    }
}

