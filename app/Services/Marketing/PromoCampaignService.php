<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\PromoCampaign;
use App\Models\PromoUse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PromoCampaignService
{
    public function createCampaign(array $data, int $tenantId, int $userId, string $correlationId = ''): PromoCampaign
    {
        return DB::transaction(function () use ($data, $tenantId, $userId, $correlationId) {
            Log::channel('promo')->info('Creating promo campaign', [
                'correlation_id' => $correlationId,
                'type' => $data['type'],
            ]);

            return PromoCampaign::create([
                'tenant_id' => $tenantId,
                'type' => $data['type'],
                'code' => $data['code'] ?? Str::upper(Str::random(8)),
                'name' => $data['name'],
                'budget' => $data['budget'] * 100, // в копейки
                'spent_budget' => 0,
                'status' => 'active',
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
                'created_by' => $userId,
                'start_at' => $data['start_at'] ?? now(),
                'end_at' => $data['end_at'] ?? now()->addMonth(),
            ]);
        });
    }

    public function applyPromo(string $code, int $tenantId, int $userId, int $amount): array
    {
        return DB::transaction(function () use ($code, $tenantId, $userId, $amount) {
            $campaign = PromoCampaign::where('code', $code)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();

            if (!$campaign || $campaign->spent_budget >= $campaign->budget) {
                Log::channel('promo')->warning('Invalid promo code', [
                    'code' => $code,
                    'tenant_id' => $tenantId,
                ]);

                return ['success' => false, 'error' => 'Invalid or exhausted promo code'];
            }

            $discount = $this->calculateDiscount($campaign, $amount);

            if ($campaign->spent_budget + $discount > $campaign->budget) {
                return ['success' => false, 'error' => 'Budget exhausted'];
            }

            PromoUse::create([
                'promo_campaign_id' => $campaign->id,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'discount_amount' => $discount,
            ]);

            $campaign->increment('spent_budget', $discount);

            Log::channel('promo')->info('Promo applied', [
                'code' => $code,
                'discount' => $discount,
            ]);

            return ['success' => true, 'discount' => $discount];
        });
    }

    private function calculateDiscount(PromoCampaign $campaign, int $amount): int
    {
        return match ($campaign->type) {
            'discount_percent' => (int)($amount * ($campaign->discount_percent ?? 10) / 100),
            'fixed_amount' => $campaign->fixed_amount ?? 1000,
            'buy_x_get_y' => (int)($amount * 0.1),
            default => 0,
        };
    }
}
