<?php

declare(strict_types=1);

namespace Modules\Advertising\Services;

use App\Models\User;
use App\Services\FraudControlService;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Modules\Advertising\Models\Campaign;
use Modules\Advertising\Models\Creative;
use Modules\Common\Services\AbstractTechnicalVerticalService;
use Modules\Wallet\Services\WalletService;

/**
 * Сервис рекламного движка (Ad Engine).
 *
 * КАНОН 2026:
 * - Создание кампаний через единую точку (никаких Campaign::create() в контроллерах)
 * - Списание бюджета через WalletService (не $user->withdraw() напрямую)
 * - FraudControlService перед публикацией кампании
 * - Логирование через LogManager (не static Log::)
 * - ОРД-маркировка через OrdService (ФЗ-38)
 */
final class AdEngine extends AbstractTechnicalVerticalService
{
    public function __construct(
        private readonly Connection          $db,
        private readonly LogManager         $log,
        private readonly FraudControlService $fraud,
        private readonly OrdService         $ord,
        private readonly WalletService      $wallet,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('advertising.enabled', true);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Campaign management
    // ──────────────────────────────────────────────────────────────────

    /**
     * Создать рекламную кампанию и списать бюджет с кошелька.
     *
     * @param User  $user  Создатель кампании
     * @param array $data  {name, budget (коп.), vertical, start_date, end_date}
     *
     * @throws \DomainException При недостаточном балансе
     * @throws \Throwable
     */
    public function createCampaign(User $user, array $data): Campaign
    {
        $correlationId = $this->getCorrelationId();
        $tenantId      = $user->tenant_id ?? $this->resolveTenantId();

        $this->log->channel('audit')->info('ad_engine.campaign.create.start', [
            'correlation_id' => $correlationId,
            'tenant_id'      => $tenantId,
            'user_id'        => $user->id,
            'budget'         => $data['budget'] ?? 0,
        ]);

        // Fraud check перед созданием кампании
        $this->fraud->check(
            userId:        $user->id,
            operationType: 'ad_campaign_create',
            amount:        (int) ($data['budget'] ?? 0),
            ipAddress:     request()->ip(),
            correlationId: $correlationId,
        );

        try {
            $campaign = $this->db->transaction(function () use ($user, $data, $tenantId, $correlationId): Campaign {
                // Списать бюджет через WalletService
                $this->wallet->withdraw(
                    userId:        $user->id,
                    tenantId:      $tenantId,
                    amountCents:   (int) ($data['budget'] ?? 0),
                    description:   'Бюджет рекламной кампании: ' . ($data['name'] ?? ''),
                    correlationId: $correlationId,
                );

                return Campaign::create([
                    'tenant_id'      => $tenantId,
                    'name'           => $data['name'],
                    'budget'         => (int) ($data['budget'] ?? 0),
                    'vertical'       => $data['vertical'],
                    'is_active'      => true,
                    'start_date'     => $data['start_date'] ?? now(),
                    'end_date'       => $data['end_date'] ?? null,
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('ad_engine.campaign.create.success', [
                'correlation_id' => $correlationId,
                'campaign_id'    => $campaign->id,
            ]);

            return $campaign;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ad_engine.campaign.create.error', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Добавить креатив к кампании и получить ОРД erid (ФЗ-38).
     *
     * @param Campaign $campaign  Кампания
     * @param array    $data      {title, content, link, ...}
     *
     * @throws \Throwable
     */
    public function addCreative(Campaign $campaign, array $data): Creative
    {
        $correlationId = $this->getCorrelationId();

        try {
            $creative = $this->db->transaction(function () use ($campaign, $data, $correlationId): Creative {
                /** @var Creative $creative */
                $creative = $campaign->creatives()->create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                // Получить erid от ОРД (ФЗ-38 — обязательная маркировка рекламы)
                $erid = $this->ord->getErid($creative);
                $creative->update(['erid' => $erid]);

                return $creative->refresh();
            });

            $this->log->channel('audit')->info('ad_engine.creative.add.success', [
                'correlation_id' => $correlationId,
                'campaign_id'    => $campaign->id,
                'creative_id'    => $creative->id,
                'erid'           => $creative->erid,
            ]);

            return $creative;
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ad_engine.creative.add.error', [
                'correlation_id' => $correlationId,
                'campaign_id'    => $campaign->id,
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Приостановить кампанию.
     */
    public function pauseCampaign(Campaign $campaign): void
    {
        $correlationId = $this->getCorrelationId();

        $this->db->transaction(static function () use ($campaign): void {
            $campaign->update(['is_active' => false]);
        });

        $this->log->channel('audit')->info('ad_engine.campaign.paused', [
            'correlation_id' => $correlationId,
            'campaign_id'    => $campaign->id,
        ]);
    }
}

