<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Modules\Payment\Domain\Entities\PaymentRecord;
use Modules\Payment\Domain\Entities\Payout;
use Modules\Payment\Domain\Repositories\CommissionRuleRepositoryInterface;
use Modules\Payment\Domain\Repositories\PayoutRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Split Payment Service - разделение платежа между продавцами и платформой.
 */
final readonly class SplitPaymentService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly PayoutService $payoutService,
        private readonly CommissionRuleRepositoryInterface $commissionRuleRepository,
    ) {}

    /**
     * @param  Collection<int, array{seller_id: int, amount_kopecks: int, item_ids: array<int>}>  $splits
     * @return array{platform_fee: int, payouts: Collection<int, Payout>}
     */
    public function processSplit(
        PaymentRecord $paymentRecord,
        Collection $splits,
        string $correlationId,
    ): array {
        $this->logger->info('Processing split payment', [
            'payment_record_id' => $paymentRecord->id,
            'correlation_id' => $correlationId,
            'splits_count' => $splits->count(),
        ]);

        return $this->db->transaction(function () use ($paymentRecord, $splits, $correlationId) {
            // 1. Получить правило комиссии для вертикали
            $commissionRule = $this->getCommissionRule(
                $paymentRecord->metadata['vertical'] ?? null,
                $paymentRecord->metadata['sub_vertical'] ?? null,
                $paymentRecord->businessGroupId,
            );

            $commissionPercent = $commissionRule?->commissionPercent ?? config('payment.default_commission_percent', 5.0);

            // 2. Рассчитать общую комиссию платформы
            $totalAmount = $splits->sum('amount_kopecks');
            $platformFee = (int) round($totalAmount * $commissionPercent / 100);

            $this->logger->info('Commission calculated', [
                'payment_record_id' => $paymentRecord->id,
                'commission_percent' => $commissionPercent,
                'total_amount' => $totalAmount,
                'platform_fee' => $platformFee,
            ]);

            // 3. Создать выплаты продавцам
            $payouts = collect();
            $totalSellerPayout = 0;

            foreach ($splits as $split) {
                $sellerAmount = $split['amount_kopecks'];
                // Пропорционально вычитаем комиссию платформы из суммы продавца
                $sellerFee = (int) round($sellerAmount * $commissionPercent / 100);
                $sellerPayoutAmount = $sellerAmount - $sellerFee;

                if ($sellerPayoutAmount <= 0) {
                    $this->logger->warning('Seller payout amount is zero or negative', [
                        'seller_id' => $split['seller_id'],
                        'original_amount' => $sellerAmount,
                        'fee' => $sellerFee,
                        'payout_amount' => $sellerPayoutAmount,
                    ]);
                    continue;
                }

                $payout = $this->payoutService->createPayout(
                    sellerId: $split['seller_id'],
                    paymentRecordId: $paymentRecord->id,
                    amountKopecks: $sellerPayoutAmount,
                    providerCode: $paymentRecord->providerCode,
                    correlationId: $correlationId,
                    metadata: [
                        'item_ids' => $split['item_ids'] ?? [],
                        'original_amount' => $sellerAmount,
                        'commission_fee' => $sellerFee,
                        'commission_percent' => $commissionPercent,
                    ],
                );

                $payouts->push($payout);
                $totalSellerPayout += $sellerPayoutAmount;
            }

            $this->audit->log(
                action: 'payment_split_processed',
                subjectType: PaymentRecord::class,
                subjectId: $paymentRecord->id,
                newValues: [
                    'platform_fee' => $platformFee,
                    'total_seller_payout' => $totalSellerPayout,
                    'payouts_count' => $payouts->count(),
                    'commission_percent' => $commissionPercent,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Split payment processed', [
                'payment_record_id' => $paymentRecord->id,
                'platform_fee' => $platformFee,
                'total_seller_payout' => $totalSellerPayout,
                'payouts_count' => $payouts->count(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'platform_fee' => $platformFee,
                'payouts' => $payouts,
            ];
        });
    }

    private function getCommissionRule(
        ?string $verticalCode,
        ?string $subVerticalCode,
        ?int $businessGroupId,
    ): ?object {
        if (!$verticalCode) {
            return null;
        }

        // Сначала ищем специфическое правило для бизнес-группы
        if ($businessGroupId) {
            $rule = $this->commissionRuleRepository->findActiveRule($verticalCode, $subVerticalCode, $businessGroupId);
            if ($rule) {
                return $rule;
            }
        }

        // Затем общее правило для вертикали
        return $this->commissionRuleRepository->findActiveRule($verticalCode, $subVerticalCode, null);
    }
}
