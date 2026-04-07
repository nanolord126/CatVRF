<?php

declare(strict_types=1);

namespace App\Domains\Insurance\RiskManagement\Services;

use App\Domains\Insurance\RiskManagement\Models\RiskAssessment;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * RiskManagementService — управление оценкой рисков.
 *
 * Создание, завершение и отмена оценок рисков для бизнеса.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class RiskManagementService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать оценку рисков.
     */
    public function createAssessment(
        int    $analystId,
        string $riskType,
        int    $hoursEstimate,
        string $dueDate,
        string $correlationId = '',
    ): RiskAssessment {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($analystId, $riskType, $hoursEstimate, $dueDate, $correlationId): RiskAssessment {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'risk_assessment',
                amount: 0,
                correlationId: $correlationId,
            );

            $ratePerHourKopecks = 300000;
            $total = $ratePerHourKopecks * $hoursEstimate;

            $assessment = RiskAssessment::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'analyst_id'     => $analystId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'risk_type'      => $riskType,
                'hours_estimate' => $hoursEstimate,
                'due_date'       => $dueDate,
                'tags'           => ['risk' => true],
            ]);

            $this->logger->info('Risk assessment created', [
                'assessment_id'  => $assessment->id,
                'correlation_id' => $correlationId,
            ]);

            return $assessment;
        });
    }

    /**
     * Завершить оценку рисков и выплатить аналитику.
     */
    public function completeAssessment(int $assessmentId, string $correlationId = ''): RiskAssessment
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($assessmentId, $correlationId): RiskAssessment {
            $assessment = RiskAssessment::findOrFail($assessmentId);

            if ($assessment->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $assessment->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $assessment->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['assessment_id' => $assessment->id],
            );

            $this->logger->info('Risk assessment completed', [
                'assessment_id'  => $assessment->id,
                'correlation_id' => $correlationId,
            ]);

            return $assessment;
        });
    }

    /**
     * Отменить оценку рисков и вернуть средства.
     */
    public function cancelAssessment(int $assessmentId, string $correlationId = ''): RiskAssessment
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($assessmentId, $correlationId): RiskAssessment {
            $assessment = RiskAssessment::findOrFail($assessmentId);

            if ($assessment->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed assessment', 400);
            }

            $wasPaid = $assessment->payment_status === 'completed';

            $assessment->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $assessment->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $assessment->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['assessment_id' => $assessment->id],
                );
            }

            $this->logger->info('Risk assessment cancelled', [
                'assessment_id'  => $assessment->id,
                'refunded'       => $wasPaid,
                'correlation_id' => $correlationId,
            ]);

            return $assessment;
        });
    }

    /**
     * Получить оценку по ID.
     */
    public function getAssessment(int $assessmentId): RiskAssessment
    {
        return RiskAssessment::findOrFail($assessmentId);
    }

    /**
     * Получить последние оценки клиента.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, RiskAssessment>
     */
    public function getUserAssessments(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return RiskAssessment::where('client_id', $clientId)
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
