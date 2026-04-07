<?php

declare(strict_types=1);

namespace App\Domains\Insurance\Services;

use App\Domains\Insurance\InsuranceServices\Models\InsurancePolicy;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * InsuranceService — корневой сервис страхового домена.
 *
 * Оркестрирует создание полисов, оплату и выплаты.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class InsuranceService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Оформить страховой полис.
     */
    public function createPolicy(
        int    $companyId,
        string $policyType,
        int    $premiumKopecks,
        string $startDate,
        string $endDate,
        string $correlationId = '',
    ): InsurancePolicy {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($companyId, $policyType, $premiumKopecks, $startDate, $endDate, $correlationId): InsurancePolicy {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'insurance_policy',
                amount: $premiumKopecks,
                correlationId: $correlationId,
            );

            $policy = InsurancePolicy::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'company_id'     => $companyId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'policy_type'    => $policyType,
                'premium_kopecks' => $premiumKopecks,
                'payout_kopecks' => $premiumKopecks - (int) ($premiumKopecks * 0.14),
                'payment_status' => 'pending',
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'tags'           => ['insurance' => true],
            ]);

            $this->logger->info('Insurance policy created', [
                'policy_id'      => $policy->id,
                'correlation_id' => $correlationId,
            ]);

            return $policy;
        });
    }

    /**
     * Активировать полис после оплаты и выплатить компании.
     */
    public function activatePolicy(int $policyId, string $correlationId = ''): InsurancePolicy
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($policyId, $correlationId): InsurancePolicy {
            $policy = InsurancePolicy::findOrFail($policyId);

            if ($policy->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $policy->update([
                'status'         => 'active',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $policy->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['policy_id' => $policy->id],
            );

            $this->logger->info('Insurance policy activated', [
                'policy_id'      => $policy->id,
                'correlation_id' => $correlationId,
            ]);

            return $policy;
        });
    }

    /**
     * Отменить полис и вернуть средства.
     */
    public function cancelPolicy(int $policyId, string $correlationId = ''): InsurancePolicy
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($policyId, $correlationId): InsurancePolicy {
            $policy = InsurancePolicy::findOrFail($policyId);

            if ($policy->status === 'active') {
                throw new \RuntimeException('Cannot cancel active policy without claim', 400);
            }

            $wasPaid = $policy->payment_status === 'completed';

            $policy->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $policy->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $policy->premium_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['policy_id' => $policy->id],
                );
            }

            $this->logger->info('Insurance policy cancelled', [
                'policy_id'      => $policy->id,
                'refunded'       => $wasPaid,
                'correlation_id' => $correlationId,
            ]);

            return $policy;
        });
    }

    /**
     * Получить полис по ID.
     */
    public function getPolicy(int $policyId): InsurancePolicy
    {
        return InsurancePolicy::findOrFail($policyId);
    }

    /**
     * Получить последние полисы клиента.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, InsurancePolicy>
     */
    public function getUserPolicies(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return InsurancePolicy::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function __toString(): string
    {
        return static::class;
    }

    /** @return array<string, mixed> */
    public function toDebugArray(): array
    {
        return [
            'class'     => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
