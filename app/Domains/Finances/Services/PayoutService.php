<?php

declare(strict_types=1);

namespace App\Domains\Finances\Services;

use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис выплат тенантам.
 *
 * Агрегирует доходы за период, проводит фрод-проверку,
 * списывает через WalletService и логирует в аудит.
 * CatVRF Canon 2026: все мутации — DB::transaction + fraud-check + correlation_id.
 *
 * @package App\Domains\Finances\Services
 */
final readonly class PayoutService
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
     * Инициировать выплату тенанту.
     *
     * @param int         $tenantWalletId  ID кошелька тенанта
     * @param int         $amountKopecks   Сумма выплаты в копейках
     * @param string      $periodLabel     Метка периода (например "2026-03")
     * @param int|null    $businessGroupId ID филиала (для B2B)
     * @param string|null $correlationId   Трейсинг-идентификатор
     *
     * @return array{wallet_id: int, amount: int, period: string, status: string}
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function initiatePayout(
        int $tenantWalletId,
        int $amountKopecks,
        string $periodLabel,
        ?int $businessGroupId = null,
        ?string $correlationId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->logger->info('Payout initiation started', [
            'wallet_id'      => $tenantWalletId,
            'amount'         => $amountKopecks,
            'period'         => $periodLabel,
            'correlation_id' => $correlationId,
        ]);

        if ($amountKopecks <= 0) {
            $this->logger->info('Payout skipped — zero or negative amount', [
                'wallet_id'      => $tenantWalletId,
                'correlation_id' => $correlationId,
            ]);

            return [
                'wallet_id' => $tenantWalletId,
                'amount'    => 0,
                'period'    => $periodLabel,
                'status'    => 'skipped',
            ];
        }

        $this->fraud->check(
            userId: $tenantWalletId,
            operationType: 'payout_initiation',
            amount: $amountKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use (
            $tenantWalletId,
            $amountKopecks,
            $periodLabel,
            $businessGroupId,
            $correlationId,
        ): array {
            $this->walletService->credit(
                walletId: $tenantWalletId,
                amount: $amountKopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: [
                    'type'              => 'payout',
                    'period'            => $periodLabel,
                    'business_group_id' => $businessGroupId,
                    'initiated_at'      => Carbon::now()->toIso8601String(),
                ],
            );

            $result = [
                'wallet_id' => $tenantWalletId,
                'amount'    => $amountKopecks,
                'period'    => $periodLabel,
                'status'    => 'initiated',
            ];

            $this->audit->record(
                'payout_initiated',
                'payout',
                $tenantWalletId,
                [],
                array_merge($result, ['business_group_id' => $businessGroupId]),
                $correlationId,
            );

            $this->logger->info('Payout initiated successfully', [
                'wallet_id'      => $tenantWalletId,
                'amount'         => $amountKopecks,
                'period'         => $periodLabel,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        });
    }

    /**
     * Подтвердить выплату (вызывается из webhook платёжного шлюза).
     *
     * @param int         $tenantWalletId ID кошелька тенанта
     * @param int         $amountKopecks  Подтверждённая сумма
     * @param string|null $correlationId  Трейсинг-идентификатор
     */
    public function confirmPayout(
        int $tenantWalletId,
        int $amountKopecks,
        ?string $correlationId = null,
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->audit->record(
            'payout_confirmed',
            'payout',
            $tenantWalletId,
            [],
            [
                'amount'       => $amountKopecks,
                'confirmed_at' => Carbon::now()->toIso8601String(),
            ],
            $correlationId,
        );

        $this->logger->info('Payout confirmed', [
            'wallet_id'      => $tenantWalletId,
            'amount'         => $amountKopecks,
            'correlation_id' => $correlationId,
        ]);
    }
}
