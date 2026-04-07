<?php

declare(strict_types=1);

namespace Modules\Commissions\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Commissions\Application\DTOs\CommissionData;
use Modules\Commissions\Domain\Repositories\CommissionRuleRepositoryInterface;
use Modules\Commissions\Domain\Repositories\CommissionTransactionRepositoryInterface;
use Modules\Commissions\Domain\Events\CommissionCalculated;
use Modules\Commissions\Domain\ValueObjects\CommissionCalculationResult;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\WalletService;

final class CalculateCommissionUseCase
{
    public function __construct(
        private readonly CommissionRuleRepositoryInterface $ruleRepository,
        private readonly CommissionTransactionRepositoryInterface $transactionRepository,
        private readonly FraudControlService $fraudControlService,
        private readonly WalletService $walletService
    ) {
    }

    public function execute(CommissionData $data): CommissionCalculationResult
    {
        RateLimiter::hit('calculate_commission:' . $data->tenant_id, 60);

        $this->fraudControlService->check();

        return DB::transaction(function () use ($data) {
            $rule = $this->ruleRepository->findByVerticalAndTenant(
                $data->vertical,
                $data->tenant_id
            );

            if (!$rule) {
                Log::channel('audit')->warning('Commission rule not found for vertical.', [
                    'vertical' => $data->vertical,
                    'tenant_id' => $data->tenant_id,
                    'correlation_id' => $data->correlation_id,
                ]);
                // Используем правило по умолчанию или выбрасываем исключение
                throw new \Exception("Commission rule for vertical '{$data->vertical}' not found.");
            }

            $commissionAmount = (int) ($data->amount * ($rule->getCommissionRate() / 10000)); // rate in basis points

            $transaction = $this->transactionRepository->create([
                'tenant_id' => $data->tenant_id,
                'rule_id' => $rule->getId(),
                'source_type' => $data->source_type,
                'source_id' => $data->source_id,
                'original_amount' => $data->amount,
                'commission_amount' => $commissionAmount,
                'correlation_id' => $data->correlation_id,
            ]);

            // Интеграция с WalletService
            $this->walletService->debit(
                walletId: $this->walletService->getTenantWallet($data->tenant_id)->id,
                amount: $commissionAmount,
                type: 'commission',
                correlationId: $data->correlation_id
            );

            $result = new CommissionCalculationResult(
                commissionAmount: $commissionAmount,
                transactionId: $transaction->getId()
            );

            event(new CommissionCalculated(
                transactionId: $transaction->getId(),
                tenantId: $data->tenant_id,
                commissionAmount: $commissionAmount,
                correlationId: $data->correlation_id
            ));

            Log::channel('audit')->info('Commission calculated successfully.', [
                'transaction_id' => $transaction->getId(),
                'tenant_id' => $data->tenant_id,
                'commission_amount' => $commissionAmount,
                'correlation_id' => $data->correlation_id,
            ]);

            return $result;
        });
    }
}
