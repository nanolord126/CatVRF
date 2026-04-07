<?php

declare(strict_types=1);

namespace App\Domains\Insurance\AssuranceServices\Services;

use App\Domains\Insurance\AssuranceServices\Models\QualityAudit;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * AssuranceServicesService — управление аудитами качества.
 *
 * Создание, завершение и отмена аудитов качества для страховых компаний.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class AssuranceServicesService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать аудит качества.
     */
    public function createAudit(
        int    $auditorId,
        string $auditType,
        int    $hoursSpent,
        string $dueDate,
        string $correlationId = '',
    ): QualityAudit {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditorId, $auditType, $hoursSpent, $dueDate, $correlationId): QualityAudit {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'assurance',
                amount: 0,
                correlationId: $correlationId,
            );

            $ratePerHourKopecks = 200000;
            $total = $ratePerHourKopecks * $hoursSpent;

            $audit = QualityAudit::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'auditor_id'     => $auditorId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'audit_type'     => $auditType,
                'hours_spent'    => $hoursSpent,
                'due_date'       => $dueDate,
                'tags'           => ['assurance' => true],
            ]);

            $this->logger->info('Quality audit created', [
                'audit_id'       => $audit->id,
                'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    /**
     * Завершить аудит и выплатить аудитору.
     */
    public function completeAudit(int $auditId, string $correlationId = ''): QualityAudit
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditId, $correlationId): QualityAudit {
            $audit = QualityAudit::findOrFail($auditId);

            if ($audit->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $audit->update([
                'status'         => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $audit->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['audit_id' => $audit->id],
            );

            $this->logger->info('Quality audit completed', [
                'audit_id'       => $audit->id,
                'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    /**
     * Отменить аудит и вернуть средства.
     */
    public function cancelAudit(int $auditId, string $correlationId = ''): QualityAudit
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditId, $correlationId): QualityAudit {
            $audit = QualityAudit::findOrFail($auditId);

            if ($audit->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed audit', 400);
            }

            $wasPaid = $audit->payment_status === 'completed';

            $audit->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $audit->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $audit->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['audit_id' => $audit->id],
                );
            }

            $this->logger->info('Quality audit cancelled', [
                'audit_id'       => $audit->id,
                'refunded'       => $wasPaid,
                'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    /**
     * Получить аудит по ID.
     */
    public function getAudit(int $auditId): QualityAudit
    {
        return QualityAudit::findOrFail($auditId);
    }

    /**
     * Получить последние аудиты клиента.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, QualityAudit>
     */
    public function getUserAudits(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return QualityAudit::where('client_id', $clientId)
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
