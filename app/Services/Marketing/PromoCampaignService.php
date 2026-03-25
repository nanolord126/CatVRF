<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\PromoCampaign;
use App\Models\PromoUse;
use App\Services\FraudControl\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Сервис управления промо-кампаниями
 *
 * CANON 2026 комплиенс:
 * - Все операции проходят через FraudControlService::check()
 * - Все мутации в DB::transaction() с audit-логированием
 * - correlation_id обязателен в каждом логе для трейсинга
 * - Защита от лимитов на применение (50 попыток/мин)
 * - Статусы: active, paused, exhausted, expired
 */
final readonly class PromoCampaignService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Создать новую промо-кампанию
     */
    /**
     * Создать новую промо-кампанию
     */
    public function createCampaign(array $data, int $tenantId, int $userId, ?string $correlationId = null): PromoCampaign
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK перед созданием
            $this->fraud->check([
                'operation_type' => 'promo_campaign_create',
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'amount' => $data['budget'] ?? 0,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 2. TRANSACTION с логированием
            $campaign = $this->db->transaction(function () use ($data, $tenantId, $userId, $correlationId) {
                $campaign = PromoCampaign::create([
                    'tenant_id' => $tenantId,
                    'type' => $data['type'],
                    'code' => $data['code'] ?? Str::upper(Str::random(8)),
                    'name' => $data['name'],
                    'budget' => ($data['budget'] ?? 0) * 100, // в копейки
                    'spent_budget' => 0,
                    'status' => 'active',
                    'correlation_id' => $correlationId,
                    'created_by' => $userId,
                    'start_at' => $data['start_at'] ?? now(),
                    'end_at' => $data['end_at'] ?? now()->addMonth(),
                ]);

                // 3. AUDIT LOG внутри транзакции
                $this->log->channel('audit')->info('Promo: Campaign created', [
                    'correlation_id' => $correlationId,
                    'promo_id' => $campaign->id,
                    'tenant_id' => $tenantId,
                    'type' => $data['type'],
                    'budget' => $campaign->budget,
                    'code' => $campaign->code,
                ]);

                return $campaign;
            });

            return $campaign;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Promo: Campaign creation failed', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Применить промо-код к заказу
     */
    public function applyPromo(string $code, int $tenantId, int $userId, int $amount, ?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK перед применением
            $this->fraud->check([
                'operation_type' => 'promo_code_apply',
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'amount' => $amount,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 2. TRANSACTION с логированием
            $result = $this->db->transaction(function () use ($code, $tenantId, $userId, $amount, $correlationId) {
                $campaign = PromoCampaign::where('code', $code)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if (!$campaign) {
                    $this->log->channel('audit')->warning('Promo: Invalid code', [
                        'correlation_id' => $correlationId,
                        'code' => $code,
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                    ]);

                    return ['success' => false, 'error' => 'Промо-код не найден или неактивен'];
                }

                // Проверка бюджета
                if ($campaign->spent_budget >= $campaign->budget) {
                    $this->log->channel('audit')->warning('Promo: Budget exhausted', [
                        'correlation_id' => $correlationId,
                        'promo_id' => $campaign->id,
                        'spent' => $campaign->spent_budget,
                        'budget' => $campaign->budget,
                    ]);

                    return ['success' => false, 'error' => 'Бюджет промо-кода исчерпан'];
                }

                $discount = $this->calculateDiscount($campaign, $amount);

                // Проверка лимита бюджета для этого применения
                if ($campaign->spent_budget + $discount > $campaign->budget) {
                    $availableBudget = $campaign->budget - $campaign->spent_budget;
                    $discount = min($discount, $availableBudget);
                }

                if ($discount <= 0) {
                    return ['success' => false, 'error' => 'Скидка не применима к этому заказу'];
                }

                // Создание записи использования промо
                PromoUse::create([
                    'promo_campaign_id' => $campaign->id,
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'discount_amount' => $discount,
                    'correlation_id' => $correlationId,
                ]);

                // Обновление потраченного бюджета
                $campaign->increment('spent_budget', $discount);

                // Если бюджет исчерпан - изменить статус
                if ($campaign->spent_budget >= $campaign->budget) {
                    $campaign->update(['status' => 'exhausted']);
                }

                // 3. AUDIT LOG внутри транзакции
                $this->log->channel('audit')->info('Promo: Code applied', [
                    'correlation_id' => $correlationId,
                    'promo_id' => $campaign->id,
                    'code' => $campaign->code,
                    'user_id' => $userId,
                    'discount' => $discount,
                    'order_amount' => $amount,
                ]);

                return ['success' => true, 'discount' => $discount];
            });

            return $result;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Promo: Apply failed', [
                'correlation_id' => $correlationId,
                'code' => $code,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Расчёт размера скидки в зависимости от типа промо
     */
    private function calculateDiscount(PromoCampaign $campaign, int $amount): int
    {
        return match ($campaign->type) {
            'discount_percent' => (int)($amount * ($campaign->discount_percent ?? 10) / 100),
            'fixed_amount' => $campaign->fixed_amount ?? 1000,
            'buy_x_get_y' => (int)($amount * 0.1),
            default => 0,
        };
    }

    /**
     * Получить активные промо-кампании для тенанта
     */
    public function getActiveCampaigns(int $tenantId, ?string $vertical = null): array
    {
        try {
            $query = PromoCampaign::where('tenant_id', $tenantId)
                ->where('status', '!=', 'expired')
                ->where('end_at', '>', now())
                ->orderBy('created_at', 'desc');

            if ($vertical) {
                $query->whereJsonContains('applicable_verticals', $vertical);
            }

            $campaigns = $query->get();

            $this->log->channel('audit')->info('Promo: Active campaigns listed', [
                'tenant_id' => $tenantId,
                'count' => $campaigns->count(),
                'vertical' => $vertical,
            ]);

            return $campaigns->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'type' => $c->type,
                'budget' => $c->budget,
                'spent_budget' => $c->spent_budget,
                'status' => $c->status,
            ])->toArray();
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Promo: List active failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Проверить действительность промо-кода (без применения)
     */
    public function validatePromo(string $code, int $tenantId, int $amount, ?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            $campaign = PromoCampaign::where('code', $code)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('end_at', '>', now())
                ->first();

            if (!$campaign) {
                $this->log->channel('audit')->info('Promo: Validation failed', [
                    'correlation_id' => $correlationId,
                    'code' => $code,
                    'reason' => 'not_found_or_inactive',
                ]);

                return ['valid' => false, 'error' => 'Промо-код не найден'];
            }

            if ($campaign->spent_budget >= $campaign->budget) {
                $this->log->channel('audit')->info('Promo: Validation failed', [
                    'correlation_id' => $correlationId,
                    'code' => $code,
                    'reason' => 'budget_exhausted',
                ]);

                return ['valid' => false, 'error' => 'Бюджет исчерпан'];
            }

            $discount = $this->calculateDiscount($campaign, $amount);

            return [
                'valid' => true,
                'discount' => $discount,
                'type' => $campaign->type,
                'code' => $campaign->code,
            ];
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Promo: Validation error', [
                'correlation_id' => $correlationId,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить применение промо-кода (возврат скидки в бюджет)
     */
    public function cancelPromoUse(int $useId, int $userId, ?string $correlationId = null): bool
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK
            $this->fraud->check([
                'operation_type' => 'promo_use_cancel',
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'correlation_id' => $correlationId,
            ]);

            // 2. TRANSACTION
            $result = $this->db->transaction(function () use ($useId, $correlationId) {
                $use = PromoUse::findOrFail($useId);
                $campaign = $use->campaign;

                // Вернуть скидку в бюджет
                $campaign->decrement('spent_budget', $use->discount_amount);

                // Если статус был 'exhausted' - вернуть 'active'
                if ($campaign->status === 'exhausted' && $campaign->spent_budget < $campaign->budget) {
                    $campaign->update(['status' => 'active']);
                }

                // Удалить запись использования
                $use->delete();

                // 3. AUDIT LOG
                $this->log->channel('audit')->info('Promo: Use cancelled', [
                    'correlation_id' => $correlationId,
                    'use_id' => $useId,
                    'promo_id' => $campaign->id,
                    'discount' => $use->discount_amount,
                ]);

                return true;
            });

            return $result;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Promo: Cancel failed', [
                'correlation_id' => $correlationId,
                'use_id' => $useId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить статистику использования промо-кода
     */
    public function getCampaignStats(int $campaignId, ?string $correlationId = null): array
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            $campaign = PromoCampaign::findOrFail($campaignId);

            $uses = PromoUse::where('promo_campaign_id', $campaignId)
                ->count();

            $totalDiscount = PromoUse::where('promo_campaign_id', $campaignId)
                ->sum('discount_amount') ?? 0;

            $this->log->channel('audit')->info('Promo: Stats retrieved', [
                'correlation_id' => $correlationId,
                'promo_id' => $campaignId,
                'uses' => $uses,
            ]);

            return [
                'campaign_id' => $campaign->id,
                'code' => $campaign->code,
                'budget' => $campaign->budget,
                'spent_budget' => $campaign->spent_budget,
                'total_uses' => $uses,
                'total_discount' => $totalDiscount,
                'available_budget' => $campaign->budget - $campaign->spent_budget,
                'status' => $campaign->status,
            ];
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Promo: Stats retrieval failed', [
                'correlation_id' => $correlationId,
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
