<?php

declare(strict_types=1);

namespace App\Domains\Finances\Services;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис дистрибуции платежей.
 *
 * Расщепляет входящий платёж на долю тенанта и комиссию платформы.
 * CatVRF Canon 2026: 14 % B2C, 8-12 % B2B (tier-зависимо).
 * Все мутации через WalletService + DB::transaction + fraud-check.
 *
 * @package App\Domains\Finances\Services
 */
final readonly class PaymentDistributionService
{
    /**
     * @param WalletService       $walletService Сервис кошельков
     * @param FraudControlService $fraud         Фрод-контроль
     * @param AuditService        $audit         Аудит-сервис
     * @param DatabaseManager     $db            Менеджер транзакций
     * @param LoggerInterface     $logger        Логгер
     */
    public function __construct(
        private WalletService $walletService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Получить ставку комиссии платформы в зависимости от B2B-тира.
     *
     * @param bool   $isB2B   Признак юридического лица
     * @param string $b2bTier Уровень: standard / silver / gold / platinum
     *
     * @return float Ставка от 0.08 до 0.14
     */
    private function getPlatformFeeRate(bool $isB2B, string $b2bTier = 'standard'): float
    {
        if (!$isB2B) {
            return 0.14;
        }

        return match ($b2bTier) {
            'platinum' => 0.08,
            'gold'     => 0.10,
            'silver'   => 0.11,
            default    => 0.12,
        };
    }

    /**
     * Распределить платёж между тенантом и платформой.
     *
     * @param int         $tenantWalletId    ID кошелька тенанта
     * @param int         $platformWalletId  ID кошелька платформы
     * @param int         $grossAmountKopecks Полная сумма в копейках
     * @param bool        $isB2B             Признак юридического лица
     * @param string      $b2bTier           Уровень B2B-тира
     * @param int|null    $businessGroupId   ID филиала (для B2B)
     * @param string|null $correlationId     Идентификатор корреляции
     *
     * @return array{gross: int, tenant_net: int, platform_fee: int}
     */
    public function distributePayment(
        int $tenantWalletId,
        int $platformWalletId,
        int $grossAmountKopecks,
        bool $isB2B,
        string $b2bTier = 'standard',
        ?int $businessGroupId = null,
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->fraud->check(
            userId: $tenantWalletId,
            operationType: 'payment_distribution',
            amount: $grossAmountKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use (
            $tenantWalletId,
            $platformWalletId,
            $grossAmountKopecks,
            $isB2B,
            $b2bTier,
            $businessGroupId,
            $correlationId,
        ): array {
            $feeRate            = $this->getPlatformFeeRate($isB2B, $b2bTier);
            $platformFeeKopecks = (int) round($grossAmountKopecks * $feeRate);
            $tenantNetKopecks   = $grossAmountKopecks - $platformFeeKopecks;

            $this->walletService->credit(
                walletId: $tenantWalletId,
                amount: $tenantNetKopecks,
                type: BalanceTransactionType::DEPOSIT,
                correlationId: $correlationId,
                metadata: [
                    'business_group_id' => $businessGroupId,
                    'is_b2b'            => $isB2B,
                    'type'              => 'tenant_order_income',
                    'gross_amount'      => $grossAmountKopecks,
                ],
            );

            if ($platformFeeKopecks > 0) {
                $this->walletService->credit(
                    walletId: $platformWalletId,
                    amount: $platformFeeKopecks,
                    type: BalanceTransactionType::COMMISSION,
                    correlationId: $correlationId,
                    metadata: [
                        'source_tenant_wallet_id' => $tenantWalletId,
                        'is_b2b'                  => $isB2B,
                        'type'                    => 'platform_fee',
                        'gross_amount'            => $grossAmountKopecks,
                    ],
                );
            }

            $result = [
                'gross'        => $grossAmountKopecks,
                'tenant_net'   => $tenantNetKopecks,
                'platform_fee' => $platformFeeKopecks,
            ];

            $this->audit->record(
                'payment_distributed',
                self::class,
                $tenantWalletId,
                [],
                array_merge($result, [
                    'is_b2b'            => $isB2B,
                    'b2b_tier'          => $b2bTier,
                    'business_group_id' => $businessGroupId,
                ]),
                $correlationId,
            );

            $this->logger->info('Payment successfully distributed', [
                'tenant_wallet_id'   => $tenantWalletId,
                'platform_wallet_id' => $platformWalletId,
                'gross'              => $grossAmountKopecks,
                'tenant_net'         => $tenantNetKopecks,
                'platform_fee'       => $platformFeeKopecks,
                'correlation_id'     => $correlationId,
            ]);

            return $result;
        });
    }
}
