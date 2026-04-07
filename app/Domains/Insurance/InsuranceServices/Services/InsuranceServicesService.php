<?php

declare(strict_types=1);

namespace App\Domains\Insurance\InsuranceServices\Services;

use App\Domains\Insurance\InsuranceServices\Models\InsuranceConsultation;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * InsuranceServicesService — управление страховыми консультациями.
 *
 * Создание, завершение и отмена консультаций по страхованию.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class InsuranceServicesService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать страховую консультацию.
     */
    public function createConsultation(
        int    $agentId,
        string $policyType,
        int    $hoursEstimate,
        string $dueDate,
        string $correlationId = '',
    ): InsuranceConsultation {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($agentId, $policyType, $hoursEstimate, $dueDate, $correlationId): InsuranceConsultation {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'insurance_consultation',
                amount: 0,
                correlationId: $correlationId,
            );

            $ratePerHourKopecks = 250000;
            $total = $ratePerHourKopecks * $hoursEstimate;

            $consultation = InsuranceConsultation::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'agent_id'       => $agentId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'policy_type'    => $policyType,
                'hours_estimate' => $hoursEstimate,
                'due_date'       => $dueDate,
                'tags'           => ['insurance' => true],
            ]);

            $this->logger->info('Insurance consultation created', [
                'consultation_id' => $consultation->id,
                'correlation_id'  => $correlationId,
            ]);

            return $consultation;
        });
    }

    /**
     * Завершить консультацию и выплатить агенту.
     */
    public function completeConsultation(int $consultationId, string $correlationId = ''): InsuranceConsultation
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($consultationId, $correlationId): InsuranceConsultation {
            $consultation = InsuranceConsultation::findOrFail($consultationId);

            if ($consultation->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $consultation->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $consultation->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['consultation_id' => $consultation->id],
            );

            $this->logger->info('Insurance consultation completed', [
                'consultation_id' => $consultation->id,
                'correlation_id'  => $correlationId,
            ]);

            return $consultation;
        });
    }

    /**
     * Отменить консультацию и вернуть средства.
     */
    public function cancelConsultation(int $consultationId, string $correlationId = ''): InsuranceConsultation
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($consultationId, $correlationId): InsuranceConsultation {
            $consultation = InsuranceConsultation::findOrFail($consultationId);

            if ($consultation->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed consultation', 400);
            }

            $wasPaid = $consultation->payment_status === 'completed';

            $consultation->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $consultation->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $consultation->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['consultation_id' => $consultation->id],
                );
            }

            $this->logger->info('Insurance consultation cancelled', [
                'consultation_id' => $consultation->id,
                'refunded'        => $wasPaid,
                'correlation_id'  => $correlationId,
            ]);

            return $consultation;
        });
    }

    /**
     * Получить консультацию по ID.
     */
    public function getConsultation(int $consultationId): InsuranceConsultation
    {
        return InsuranceConsultation::findOrFail($consultationId);
    }

    /**
     * Получить последние консультации клиента.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, InsuranceConsultation>
     */
    public function getUserConsultations(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return InsuranceConsultation::where('client_id', $clientId)
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
