<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Wallet\Transaction;
use App\Models\AML\AMLAlert;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use App\Models\User;
use Carbon\CarbonImmutable;
use App\Traits\WithAuditLogging;

final readonly class AMLScreeningService
{
    use WithAuditLogging;

    private const SCREENING_THRESHOLD_RUB = 10000;

    private const HIGH_RISK_THRESHOLD = 0.7;

    private const CRITICAL_RISK_THRESHOLD = 0.9;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly HttpFactory $http,
        private readonly LogManager $log,
        private readonly Repository $config,
    ) {}

    /**
     * Screen transaction for AML compliance
     */
    public function screenTransaction(
        int $transactionId,
        string $correlationId = ''
    ): array {
        $transaction = Transaction::with(['sender', 'receiver'])->findOrFail($transactionId);

        $this->fraudControl->check(
            userId: $transaction->sender_id,
            operationType: 'aml_screening',
            amount: $transaction->amount,
            correlationId: $correlationId,
        );

        // Only screen transactions above threshold
        if ($transaction->amount < self::SCREENING_THRESHOLD_RUB) {
            return [
                'screened' => false,
                'reason' => 'below_threshold',
                'threshold' => self::SCREENING_THRESHOLD_RUB,
            ];
        }

        // Screen against sanctions lists
        $sanctionsCheck = $this->screenSanctions($transaction, $correlationId);

        // Screen beneficiary
        $beneficiaryCheck = $this->screenBeneficiary($transaction, $correlationId);

        // Calculate overall risk
        $overallRisk = $this->calculateOverallRisk($sanctionsCheck, $beneficiaryCheck);

        // Create AML alert if high risk
        if ($overallRisk['score'] >= self::HIGH_RISK_THRESHOLD) {
            $this->createAMLAlert($transaction, $overallRisk, $correlationId);
        }

        // Auto-freeze if critical risk
        if ($overallRisk['score'] >= self::CRITICAL_RISK_THRESHOLD) {
            $this->freezeTransaction($transaction, $correlationId);
        }

        // Audit log
        $this->audit->record(
            action: 'aml_screening_completed',
            subjectType: Transaction::class,
            subjectId: $transaction->id,
            newValues: [
                'risk_score' => $overallRisk['score'],
                'risk_level' => $overallRisk['level'],
                'auto_frozen' => $overallRisk['score'] >= self::CRITICAL_RISK_THRESHOLD,
            ],
            correlationId: $correlationId,
        );

        return [
            'screened' => true,
            'risk_score' => $overallRisk['score'],
            'risk_level' => $overallRisk['level'],
            'sanctions_check' => $sanctionsCheck,
            'beneficiary_check' => $beneficiaryCheck,
            'auto_frozen' => $overallRisk['score'] >= self::CRITICAL_RISK_THRESHOLD,
        ];
    }

    /**
     * Screen user against sanctions lists
     */
    public function screenUser(
        int $userId,
        string $correlationId = ''
    ): array {
        $user = User::with('wallet')->findOrFail($userId);

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'aml_user_screening',
            amount: 0,
            correlationId: $correlationId,
        );

        // Screen against Росфинмониторинг
        $rosfinResult = $this->screenRosfinmonitoring($user, $correlationId);

        // Screen against OFAC
        $ofacResult = $this->screenOFAC($user, $correlationId);

        // Screen against EU sanctions
        $euResult = $this->screenEUSanctions($user, $correlationId);

        $overallRisk = $this->calculateUserRisk($rosfinResult, $ofacResult, $euResult);

        // Create alert if high risk
        if ($overallRisk['score'] >= self::HIGH_RISK_THRESHOLD) {
            $this->createUserAMLAlert($user, $overallRisk, $correlationId);
        }

        // Auto-freeze wallet if critical
        if ($overallRisk['score'] >= self::CRITICAL_RISK_THRESHOLD) {
            $this->freezeUserWallet($user, $correlationId);
        }

        return [
            'screened' => true,
            'risk_score' => $overallRisk['score'],
            'risk_level' => $overallRisk['level'],
            'rosfinmonitoring' => $rosfinResult,
            'ofac' => $ofacResult,
            'eu_sanctions' => $euResult,
        ];
    }

    private function screenSanctions(Transaction $transaction, string $correlationId): array
    {
        // Check sender and receiver against sanctions lists
        $senderResult = $this->screenUser($transaction->sender_id, $correlationId);
        $receiverResult = $this->screenUser($transaction->receiver_id, $correlationId);

        return [
            'sender_risk' => $senderResult['risk_score'],
            'receiver_risk' => $receiverResult['risk_score'],
            'max_risk' => max($senderResult['risk_score'], $receiverResult['risk_score']),
        ];
    }

    private function screenBeneficiary(Transaction $transaction, string $correlationId): array
    {
        // Check beneficiary details for patterns
        $patterns = $this->detectSuspiciousPatterns($transaction);

        return [
            'patterns_detected' => $patterns,
            'pattern_count' => count($patterns),
            'risk_score' => min(count($patterns) * 0.2, 1.0),
        ];
    }

    private function detectSuspiciousPatterns(Transaction $transaction): array
    {
        $patterns = [];

        // Pattern 1: Structuring (multiple small transactions)
        $recentTransactions = Transaction::where('sender_id', $transaction->sender_id)
            ->where('created_at', '>', CarbonImmutable::now()->subHours(24))
            ->count();

        if ($recentTransactions > 10) {
            $patterns[] = 'structuring';
        }

        // Pattern 2: Round numbers (common in money laundering)
        if ($transaction->amount % 10000 === 0) {
            $patterns[] = 'round_amount';
        }

        // Pattern 3: Rapid succession
        $lastTransaction = Transaction::where('sender_id', $transaction->sender_id)
            ->orderByDesc('created_at')
            ->skip(1)
            ->first();

        if ($lastTransaction && $lastTransaction->created_at->diffInMinutes(CarbonImmutable::now()) < 5) {
            $patterns[] = 'rapid_succession';
        }

        // Pattern 4: Geographic anomaly
        if ($transaction->sender->country !== $transaction->receiver->country) {
            $patterns[] = 'cross_border';
        }

        return $patterns;
    }

    private function screenRosfinmonitoring(User $user, string $correlationId): array
    {
        $apiKey = $this->config->get('aml.rosfinmonitoring.api_key');
        $apiUrl = $this->config->get('aml.rosfinmonitoring.api_url');

        if (! $apiKey || ! $apiUrl) {
            return ['screened' => false, 'match' => false, 'score' => 0];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->post($apiUrl.'/check', [
                    'inn' => $user->inn,
                    'name' => $user->full_name,
                ]);

            if (! $response->successful()) {
                $this->log->warning('Rosfinmonitoring check failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);

                return ['screened' => false, 'match' => false, 'score' => 0];
            }

            $data = $response->json();

            return [
                'screened' => true,
                'match' => $data['match'] ?? false,
                'score' => $data['match'] ? 1.0 : 0,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            $this->log->error('Rosfinmonitoring error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['screened' => false, 'match' => false, 'score' => 0];
        }
    }

    private function screenOFAC(User $user, string $correlationId): array
    {
        $apiKey = $this->config->get('aml.ofac.api_key');
        $apiUrl = $this->config->get('aml.ofac.api_url');

        if (! $apiKey || ! $apiUrl) {
            return ['screened' => false, 'match' => false, 'score' => 0];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->post($apiUrl.'/screen', [
                    'name' => $user->full_name,
                ]);

            if (! $response->successful()) {
                $this->log->warning('OFAC check failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);

                return ['screened' => false, 'match' => false, 'score' => 0];
            }

            $data = $response->json();

            return [
                'screened' => true,
                'match' => $data['match'] ?? false,
                'score' => $data['match'] ? 1.0 : 0,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            $this->log->error('OFAC error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['screened' => false, 'match' => false, 'score' => 0];
        }
    }

    private function screenEUSanctions(User $user, string $correlationId): array
    {
        $apiKey = $this->config->get('aml.eu_sanctions.api_key');
        $apiUrl = $this->config->get('aml.eu_sanctions.api_url');

        if (! $apiKey || ! $apiUrl) {
            return ['screened' => false, 'match' => false, 'score' => 0];
        }

        try {
            $response = $this->http->withToken($apiKey)
                ->timeout(30)
                ->post($apiUrl.'/check', [
                    'name' => $user->full_name,
                ]);

            if (! $response->successful()) {
                $this->log->warning('EU sanctions check failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);

                return ['screened' => false, 'match' => false, 'score' => 0];
            }

            $data = $response->json();

            return [
                'screened' => true,
                'match' => $data['match'] ?? false,
                'score' => $data['match'] ? 1.0 : 0,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            $this->log->error('EU sanctions error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['screened' => false, 'match' => false, 'score' => 0];
        }
    }

    private function calculateOverallRisk(array $sanctionsCheck, array $beneficiaryCheck): array
    {
        $sanctionsRisk = $sanctionsCheck['max_risk'] ?? 0;
        $beneficiaryRisk = $beneficiaryCheck['risk_score'] ?? 0;

        // Weighted average: sanctions are more important
        $score = ($sanctionsRisk * 0.7) + ($beneficiaryRisk * 0.3);

        $level = match (true) {
            $score >= self::CRITICAL_RISK_THRESHOLD => 'critical',
            $score >= self::HIGH_RISK_THRESHOLD => 'high',
            $score >= 0.3 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
        ];
    }

    private function calculateUserRisk(array $rosfin, array $ofac, array $eu): array
    {
        $score = max(
            $rosfin['score'] ?? 0,
            $ofac['score'] ?? 0,
            $eu['score'] ?? 0
        );

        $level = match (true) {
            $score >= self::CRITICAL_RISK_THRESHOLD => 'critical',
            $score >= self::HIGH_RISK_THRESHOLD => 'high',
            $score >= 0.3 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
        ];
    }

    private function createAMLAlert(Transaction $transaction, array $risk, string $correlationId): void
    {
        AMLAlert::create([
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->sender_id,
            'alert_type' => 'transaction_screening',
            'risk_score' => (int) round($risk['score'] * 100),
            'risk_level' => $risk['level'],
            'details' => $risk,
            'status' => 'pending_review',
            'created_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        $this->log->warning('AML alert created', [
            'transaction_id' => $transaction->id,
            'risk_level' => $risk['level'],
        ]);
    }

    private function createUserAMLAlert(User $user, array $risk, string $correlationId): void
    {
        AMLAlert::create([
            'user_id' => $user->id,
            'alert_type' => 'user_screening',
            'risk_score' => (int) round($risk['score'] * 100),
            'risk_level' => $risk['level'],
            'details' => $risk,
            'status' => 'pending_review',
            'created_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);

        $this->log->warning('AML user alert created', [
            'user_id' => $user->id,
            'risk_level' => $risk['level'],
        ]);
    }

    private function freezeTransaction(Transaction $transaction, string $correlationId): void
    {
        $transaction->update([
            'status' => 'frozen',
            'freeze_reason' => 'aml_critical_risk',
            'frozen_at' => CarbonImmutable::now(),
        ]);

        $this->log->critical('Transaction auto-frozen due to AML risk', [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
        ]);
    }

    private function freezeUserWallet(User $user, string $correlationId): void
    {
        if ($user->wallet) {
            $user->wallet->update([
                'status' => 'frozen',
                'freeze_reason' => 'aml_critical_risk',
                'frozen_at' => CarbonImmutable::now(),
            ]);

            $this->log->critical('Wallet auto-frozen due to AML risk', [
                'user_id' => $user->id,
                'wallet_id' => $user->wallet->id,
            ]);
        }
    }
}
