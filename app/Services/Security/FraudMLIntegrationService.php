<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTO\Security\FraudMLResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

/**
 * Fraud ML Integration Service
 *
 * Integration layer for FraudMLService across different business flows.
 * Provides unified interface for Auth, KYB, Wallet, and other flows.
 *
 * Production 2026 CANON:
 * - Centralized fraud checking for all sensitive operations
 * - Consistent action enforcement (block, challenge, cooldown)
 * - Audit logging for all fraud checks
 * - Fail-open behavior when ML is unavailable
 */
final readonly class FraudMLIntegrationService
{
    private const HIGH_VALUE_THRESHOLD = 100000; // 100k RUB threshold for high-value transactions

    public function __construct(
        private readonly FraudMLService $fraudML,
        private readonly LogManager $log,
    ) {}

    /**
     * Check fraud risk for login
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkLoginRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'login',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for registration
     *
     * @param  Request  $request
     * @return FraudMLResult
     */
    public function checkRegistrationRisk(Request $request): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: null,
            actionType: 'register',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for KYB verification
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkKYBRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'kyb',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for withdrawal
     *
     * @param  Request  $request
     * @param  User  $user
     * @param  float  $amount
     * @param  string  $currency
     * @return FraudMLResult
     */
    public function checkWithdrawalRisk(
        Request $request,
        User $user,
        float $amount,
        string $currency = 'RUB'
    ): FraudMLResult {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'withdrawal',
            transactionContext: [
                'amount' => $amount,
                'currency' => $currency,
                'is_high_value' => $amount > 100000,
            ],
        );
    }

    /**
     * Check fraud risk for transfer
     *
     * @param  Request  $request
     * @param  User  $user
     * @param  float  $amount
     * @param  string|null  $recipient
     * @return FraudMLResult
     */
    public function checkTransferRisk(
        Request $request,
        User $user,
        float $amount,
        ?string $recipient = null
    ): FraudMLResult {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'transfer',
            transactionContext: [
                'amount' => $amount,
                'recipient' => $recipient,
                'is_high_value' => $amount > self::HIGH_VALUE_THRESHOLD,
            ],
        );
    }

    /**
     * Check fraud risk for bank account change
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkBankChangeRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'change_bank',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for email change
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkEmailChangeRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'email_change',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for phone change
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkPhoneChangeRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'phone_change',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for password change
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkPasswordChangeRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'password_change',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for data export
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkDataExportRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'data_export',
            transactionContext: [],
        );
    }

    /**
     * Check fraud risk for staff invitation
     *
     * @param  Request  $request
     * @param  User  $user
     * @return FraudMLResult
     */
    public function checkStaffInviteRisk(Request $request, User $user): FraudMLResult
    {
        return $this->fraudML->predictRisk(
            request: $request,
            user: $user,
            actionType: 'staff_invite',
            transactionContext: [],
        );
    }

    /**
     * Handle fraud result - throw exception if blocked
     *
     * @param  FraudMLResult  $result
     * @param  string  $operation
     * @return void
     * @throws \RuntimeException
     */
    public function handleFraudResult(FraudMLResult $result, string $operation): void
    {
        if ($result->shouldBlock()) {
            $this->log->warning('Operation blocked by fraud ML', [
                'operation' => $operation,
                'fraud_score' => $result->fraudScore,
                'risk_level' => $result->riskLevel,
                'correlation_id' => $result->correlationId,
            ]);

            throw new \RuntimeException(
                sprintf(
                    'Operation blocked due to high fraud risk (score: %.2f, level: %s). Correlation ID: %s',
                    $result->fraudScore,
                    $result->riskLevel,
                    $result->correlationId
                )
            );
        }

        if ($result->shouldChallenge()) {
            $this->log->info('Operation requires additional verification', [
                'operation' => $operation,
                'fraud_score' => $result->fraudScore,
                'risk_level' => $result->riskLevel,
                'correlation_id' => $result->correlationId,
            ]);

            throw new \RuntimeException(
                sprintf(
                    'Additional verification required (score: %.2f, level: %s). Correlation ID: %s',
                    $result->fraudScore,
                    $result->riskLevel,
                    $result->correlationId
                ),
                0,
                null,
                ['requires_verification' => true, 'correlation_id' => $result->correlationId]
            );
        }
    }

    /**
     * Check if fraud ML is enabled
     */
    public function isEnabled(): bool
    {
        return config('fraud-ml.enabled', true);
    }
}
