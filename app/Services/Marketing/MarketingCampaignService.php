<?php declare(strict_types=1);

namespace App\Services\Marketing;


use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\FraudControl\FraudControlService;
use App\Services\WalletService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * MarketingCampaignService — управление рекламными кампаниями.
 *
 * Правила канона:
 *  - Бюджет кампании списывается ТОЛЬКО через WalletService::debit()
 *  - FraudControlService::check() перед созданием кампании
 *  - correlation_id обязателен в каждом событии
 *  - Все данные анонимизированы (tenant-aware, не raw user_id)
 *  - B2C и B2B — разные таргетинговые параметры
 */
final readonly class MarketingCampaignService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private AuditService        $audit,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Создать рекламную кампанию и списать бюджет с кошелька tenant'а.
     *
     * @param array{
     *   name: string,
     *   type: string,
     *   budget_kopecks: int,
     *   targeting: array,
     *   wallet_id: int,
     *   correlation_id?: string,
     * } $dto
     */
    public function createCampaign(array $dto, int $tenantId, int $userId): array
    {
        $correlationId = $dto['correlation_id'] ?? Str::uuid()->toString();

        $this->fraud->check($userId, 'marketing_campaign_create', $dto['budget_kopecks'], (string) $this->request->ip(), null, $correlationId);

        return $this->db->transaction(function () use ($dto, $tenantId, $userId, $correlationId): array {
            // Списание бюджета из Wallet tenant'а
            $debited = $this->wallet->debit(
                walletId:      $dto['wallet_id'],
                amount:        $dto['budget_kopecks'],
                reason:        'marketing_spend',
                correlationId: $correlationId,
            );

            if (! $debited) {
                throw new \RuntimeException('Insufficient wallet balance for marketing campaign');
            }

            $campaign = $this->db->table('marketing_campaigns')->insertGetId([
                'tenant_id'      => $tenantId,
                'budget_kopecks' => $dto['budget_kopecks'],
                'type'           => $dto['type'],
                'status'         => 'active',
                'correlation_id' => $correlationId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $this->logger->channel('audit')->info('Marketing campaign created', [
                'campaign_id'    => $campaign,
                'budget_kopecks' => $dto['budget_kopecks'],
                'type'           => $dto['type'],
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return ['id' => $campaign, 'correlation_id' => $correlationId];
        });
    }

    /**
     * Зафиксировать расход бюджета (impression / click / send).
     */
    public function recordSpend(int $campaignId, int $amountKopecks, string $correlationId): void
    {
        $this->db->table('marketing_campaigns')
            ->where('id', $campaignId)
            ->increment('spent_kopecks', $amountKopecks, ['updated_at' => now()]);

        $this->logger->channel('audit')->debug('Marketing spend recorded', [
            'campaign_id'    => $campaignId,
            'amount_kopecks' => $amountKopecks,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Приостановить кампанию (когда бюджет исчерпан или вручную).
     */
    public function pauseCampaign(int $campaignId, string $reason, string $correlationId): void
    {
        $this->db->table('marketing_campaigns')
            ->where('id', $campaignId)
            ->update(['status' => 'paused', 'updated_at' => now()]);

        $this->logger->channel('audit')->info('Marketing campaign paused', [
            'campaign_id'    => $campaignId,
            'reason'         => $reason,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить активные кампании tenant'а.
     */
    public function getActiveCampaigns(int $tenantId): array
    {
        return $this->db->table('marketing_campaigns')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->get()
            ->toArray();
    }
}
