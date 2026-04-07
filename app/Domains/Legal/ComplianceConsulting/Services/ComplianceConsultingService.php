<?php

declare(strict_types=1);

namespace App\Domains\Legal\ComplianceConsulting\Services;

use App\Domains\Legal\ComplianceConsulting\Models\ComplianceAudit;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ComplianceConsultingService — управление комплаенс-аудитами.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class ComplianceConsultingService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать комплаенс-аудит.
     */
    public function createAudit(
        int    $consultantId,
        string $auditType,
        int    $hoursEstimate,
        string $dueDate,
        string $correlationId = '',
    ): ComplianceAudit {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($consultantId, $auditType, $hoursEstimate, $dueDate, $correlationId): ComplianceAudit {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'compliance_audit',
                amount: 0,
                correlationId: $correlationId,
            );

            $rateKopecks = 350000;
            $total = $rateKopecks * $hoursEstimate;

            $audit = ComplianceAudit::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'consultant_id'  => $consultantId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'audit_type'     => $auditType,
                'hours_estimate' => $hoursEstimate,
                'due_date'       => $dueDate,
                'tags'           => ['compliance' => true],
            ]);

            $this->logger->info('Compliance audit created', [
                'audit_id'       => $audit->id,
                'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    public function completeAudit(int $auditId, string $correlationId = ''): ComplianceAudit
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditId, $correlationId): ComplianceAudit {
            $audit = ComplianceAudit::findOrFail($auditId);

            if ($audit->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $audit->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $audit->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['audit_id' => $audit->id],
            );

            $this->logger->info('Compliance audit completed', [
                'audit_id' => $audit->id, 'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    public function cancelAudit(int $auditId, string $correlationId = ''): ComplianceAudit
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditId, $correlationId): ComplianceAudit {
            $audit = ComplianceAudit::findOrFail($auditId);

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

            $this->logger->info('Compliance audit cancelled', [
                'audit_id' => $audit->id, 'refunded' => $wasPaid, 'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    public function getAudit(int $auditId): ComplianceAudit
    {
        return ComplianceAudit::findOrFail($auditId);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, ComplianceAudit> */
    public function getUserAudits(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ComplianceAudit::where('client_id', $clientId)->orderByDesc('created_at')->take($limit)->get();
    }

    public function __toString(): string
    {
        return static::class;
    }

    /** @return array<string, mixed> */
    public function toDebugArray(): array
    {
        return ['class' => static::class, 'timestamp' => Carbon::now()->toIso8601String()];
    }
}
