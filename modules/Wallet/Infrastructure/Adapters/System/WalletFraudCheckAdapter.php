<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Adapters\System;

use Modules\Wallet\Application\Ports\FraudCheckPort;
use App\Services\FraudMLService; // Assuming the existing core AI service architecture
use App\DTOs\OperationDto; // Assuming existing DTO logic
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class WalletFraudCheckAdapter
 *
 * Implements the FraudCheckPort to run strict checks specific to Wallet 
 * mutations (e.g. transfers, payouts, bulk deductions). It connects the pure
 * Wallet Application layer to the heavy ML-based Core Fraud Detection logic without
 * tightly coupling the UseCases to external SDKs or external ML models.
 */
final readonly class WalletFraudCheckAdapter implements FraudCheckPort
{
    /**
     * WalletFraudCheckAdapter constructor.
     * 
     * @param FraudMLService $fraudMLService The machine learning service analyzing patterns (injected by Laravel container).
     */
    public function __construct(
        private FraudMLService $fraudMLService
    ) {
    }

    /**
     * Verifies if a specific mutation operation triggers fraud boundaries.
     * Immediately throws a security exception if the behavior is categorized as malicious or severely risky.
     *
     * @param string $walletId The identity of the wallet being mutated.
     * @param int $amount Amount under mutation (in minimal units like kopecks).
     * @param string $tenantId The isolated tenant context.
     * @param string $operationType Broad categorization tag for the ML engine (e.g., 'credit', 'debit', 'transfer_out').
     * @param string $correlationId Unique request tracer for tracing the ML decision logic back to an origination point.
     * @return void
     * @throws RuntimeException If the fraud score exceeds the allowed operational threshold.
     */
    public function verifyOperation(
        string $walletId,
        int $amount,
        string $tenantId,
        string $operationType,
        string $correlationId
    ): void {
        try {
            // Build the standard Core Platform operation transfer object for the ML analysis
            $operationDto = new OperationDto(
                tenantId: $tenantId,
                userId: $walletId, // Using wallet identity as the acting subject
                operationType: 'wallet_' . $operationType,
                amount: $amount,
                correlationId: $correlationId,
                ipAddress: request()->ip() ?? '127.0.0.1',
                deviceFingerprint: request()->header('User-Agent') ?? 'System'
            );

            // Fetch the calculated probability score from the trained XGBoost/LightGBM model
            $fraudScore = $this->fraudMLService->scoreOperation($operationDto);

            // Audit precisely the calculated metric for monitoring dashboards
            Log::channel('fraud_alert')->info('Wallet operation fraud check executed', [
                'correlation_id' => $correlationId,
                'wallet_id'      => $walletId,
                'operation_type' => $operationType,
                'ml_score'       => $fraudScore,
                'amount'         => $amount,
            ]);

            // Compare specific threshold bounds based on operation classification rules
            $shouldBlock = $this->fraudMLService->shouldBlock($fraudScore, $operationDto->operationType);

            if ($shouldBlock) {
                Log::channel('fraud_alert')->warning('Wallet operation blocked by ML fraud system', [
                    'correlation_id' => $correlationId,
                    'wallet_id'      => $walletId,
                    'operation_type' => $operationType,
                    'ml_score'       => $fraudScore,
                ]);

                throw new RuntimeException(
                    "Security Constraint Violation: Operation [{$operationType}] on Wallet [{$walletId}] blocked " . 
                    "due to exceeding maximum acceptable risk threshold."
                );
            }

        } catch (RuntimeException $exception) {
            // Bubble up the explicit runtime violation
            throw $exception;
        } catch (Throwable $exception) {
            // If the ML service itself strictly fails, fallback mechanics log the failure but do not necessarily block 
            // unless configured in strict mode. Here we enforce a strict-fail policy for Wallet mutations.
            Log::channel('fraud_alert')->error('Wallet fraud check mechanism encountered an internal failure', [
                'correlation_id' => $correlationId,
                'error'          => $exception->getMessage(),
            ]);

            // Fail securely if verification layer is offline for critical modules like Wallet balance mutations.
            throw new RuntimeException(
                "Internal Validation Error: Unable to verify fraud status for Wallet [{$walletId}].",
                0,
                $exception
            );
        }
    }
}
