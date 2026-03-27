<?php

namespace App\Domains\Common\Services\Marketing;

use App\Models\User;
use App\Domains\Common\Models\{ExclusiveMerch, MerchRedemption};
use App\Domains\Finances\Services\Security\FraudControlService;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class MerchEngine
{
    private string $correlationId;
    private ?string $tenantId;

    public function __construct(
        protected FraudControlService $fraudControl,
        protected LoyaltyEngine $loyalty
    ) {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Обмен баллов на лимитированный мерч.
     */
    public function redeemMerch(User $user, int $merchId, array $deliveryDetails): MerchRedemption
    {
        $this->correlationId = Str::uuid();

        try {
            Log::channel('merch')->info('MerchEngine: redeeming merch', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'merch_id' => $merchId,
            ]);

            return DB::transaction(function () use ($user, $merchId, $deliveryDetails) {
                $merch = ExclusiveMerch::lockForUpdate()->find($merchId);

                if (!$merch || !$merch->is_available || $merch->stock_quantity <= 0) {
                    throw new \RuntimeException("Мерч недоступен или закончился на складе.");
                }

                // Проверка фрода перед списанием
                if ($this->fraudControl->assessRisk($user, ['amount' => $merch->points_price]) > 80) {
                    throw new \RuntimeException("Операция заблокирована системой безопасности (High Risk).");
                }

                $currentPoints = $user->getWalletBalance('loyalty_points') ?? 0;

                if ($currentPoints < $merch->points_price) {
                    throw new \RuntimeException("Недостаточно баллов. Требуется: {$merch->points_price}, доступно: {$currentPoints}");
                }

                // Списание баллов
                $user->withdraw($merch->points_price, [
                    'type' => 'merch_redemption',
                    'merch_id' => $merch->id,
                    'correlation_id' => $this->correlationId,
                ], 'loyalty_points');

                // Резервирование единицы товара
                $merch->decrement('stock_quantity');
                $merch->increment('redeemed_count');

                // Создание записи об обмене
                $redemption = MerchRedemption::create([
                    'user_id' => $user->id,
                    'merch_id' => $merch->id,
                    'points_spent' => $merch->points_price,
                    'delivery_address' => $deliveryDetails['address'] ?? null,
                    'delivery_status' => 'pending_verification',
                    'redeemed_at' => Carbon::now(),
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                    'metadata' => [
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]
                ]);

                // Логирование
                AuditLog::create([
                    'entity_type' => MerchRedemption::class,
                    'entity_id' => $redemption->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                    'changes' => [],
                    'metadata' => [
                        'user_id' => $user->id,
                        'merch_id' => $merchId,
                        'points_spent' => $merch->points_price,
                        'delivery_address' => $deliveryDetails['address'] ?? null,
                    ],
                ]);

                Log::channel('merch')->info('MerchEngine: merch redeemed successfully', [
                    'correlation_id' => $this->correlationId,
                    'redemption_id' => $redemption->id,
                    'user_id' => $user->id,
                    'points_spent' => $merch->points_price,
                ]);

                return $redemption;
            });
        } catch (Throwable $e) {
            Log::error('MerchEngine: redemption failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'merch_id' => $merchId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Генерация "AI-дизайна" для персонализированного мерча (концепт).
     */
    public function generateCustomPrint(User $user, string $baseItem): string
    {
        try {
            Log::channel('merch')->info('MerchEngine: generating custom print', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'base_item' => $baseItem,
            ]);

            // Здесь мы можем использовать DALL-E 3 для создания уникального принта
            $customUrl = "https://ai-studio.catvrf.ru/custom-prints/{$user->id}-{$baseItem}.png";

            Log::channel('merch')->info('MerchEngine: custom print generated', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'url' => $customUrl,
            ]);

            return $customUrl;
        } catch (Throwable $e) {
            Log::error('MerchEngine: custom print generation failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }
}
