<?php declare(strict_types=1);

namespace App\Domains\Finances\Domain\Services;

use App\Services\WalletService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Боевой модуль дистрибуции платежей уровня Enterprise.
 * Обрабатывает расплитовку бюджетов: комиссии, налоги, выплаты тенантам.
 * CatVRF Canon 2026: 14% B2C, 8-12% B2B. Все через WalletService + Fraud.
 */
final readonly class PaymentDistributionService
{
    public function __construct(
        private WalletService $walletService,
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger
    ) {}

    public function distributePayment(
        int $tenantWalletId, 
        int $platformWalletId,
        int $grossAmountKopecks, 
        bool $isB2B, 
        ?int $businessGroupId = null, 
        ?string $correlationId = null
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        // 1. Фрод-проверка. Передаем userId 0, так как это системная транзакция 
        // распределения, сам tenantWalletId – это не User.
        $this->fraud->check(
            userId: $tenantWalletId, 
            operationType: 'payment_distribution', 
            amount: $grossAmountKopecks, 
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($tenantWalletId, $platformWalletId, $grossAmountKopecks, $isB2B, $businessGroupId, $correlationId) {
            
            // 2. Расчет комиссий (CatVRF 2026: 14% B2C, 10% B2B для упрощения, tier logic later)
            $platformFeeRate = $isB2B ? 0.10 : 0.14; 
            $platformFeeKopecks = (int) round($grossAmountKopecks * $platformFeeRate);
            $tenantNetKopecks = $grossAmountKopecks - $platformFeeKopecks;

            // 3. Зачисление доли тенанту
            $this->walletService->credit(
                walletId: $tenantWalletId,
                amount: $tenantNetKopecks,
                type: BalanceTransactionType::DEPOSIT,
                correlationId: $correlationId,
                metadata: [
                    'business_group_id' => $businessGroupId,
                    'is_b2b' => $isB2B,
                    'type' => 'tenant_order_income',
                    'gross_amount' => $grossAmountKopecks
                ]
            );

            // 4. Зачисление комиссии платформе
            if ($platformFeeKopecks > 0) {
                $this->walletService->credit(
                    walletId: $platformWalletId,
                    amount: $platformFeeKopecks,
                    type: BalanceTransactionType::COMMISSION,
                    correlationId: $correlationId,
                    metadata: [
                        'source_tenant_wallet_id' => $tenantWalletId,
                        'is_b2b' => $isB2B,
                        'type' => 'platform_fee',
                        'gross_amount' => $grossAmountKopecks
                    ]
                );
            }

            // 5. Аудит
            $this->audit->record(
                'payment_distributed',
                self::class,
                $tenantWalletId,
                [],
                [
                    'gross_amount_kopecks' => $grossAmountKopecks,
                    'tenant_net_kopecks' => $tenantNetKopecks,
                    'platform_fee_kopecks' => $platformFeeKopecks,
                    'is_b2b' => $isB2B,
                    'business_group_id' => $businessGroupId
                ],
                $correlationId
            );

            $this->logger->info('Payment successfully distributed', [
                'tenant_wallet_id' => $tenantWalletId,
                'platform_wallet_id' => $platformWalletId,
                'gross' => $grossAmountKopecks,
                'tenant_net' => $tenantNetKopecks,
                'platform_fee' => $platformFeeKopecks,
                'correlation_id' => $correlationId
            ]);

            return [
                'gross' => $grossAmountKopecks,
                'tenant_net' => $tenantNetKopecks,
                'platform_fee' => $platformFeeKopecks
            ];
        });
    }
}
