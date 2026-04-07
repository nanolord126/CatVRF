<?php

declare(strict_types=1);

namespace App\Domains\Legal\DataPrivacy\Services;

use App\Domains\Legal\DataPrivacy\Models\PrivacyAudit;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * DataPrivacyService — управление аудитами приватности данных.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class DataPrivacyService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать аудит приватности.
     */
    public function createAudit(
        int    $consultantId,
        string $auditScope,
        string $regulations,
        string $correlationId = '',
    ): PrivacyAudit {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($consultantId, $auditScope, $regulations, $correlationId): PrivacyAudit {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'privacy_audit',
                amount: 0,
                correlationId: $correlationId,
            );

            $rateKopecks = 400000;

            $audit = PrivacyAudit::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'consultant_id'  => $consultantId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $rateKopecks,
                'payout_kopecks' => $rateKopecks - (int) ($rateKopecks * 0.14),
                'payment_status' => 'pending',
                'audit_scope'    => $auditScope,
                'regulations'    => $regulations,
                'tags'           => ['privacy' => true, 'gdpr' => true],
            ]);

            $this->logger->info('Privacy audit created', [
                'audit_id'       => $audit->id,
                'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    public function completeAudit(int $auditId, string $correlationId = ''): PrivacyAudit
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditId, $correlationId): PrivacyAudit {
            $audit = PrivacyAudit::findOrFail($auditId);

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

            $this->logger->info('Privacy audit completed', [
                'audit_id' => $audit->id, 'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    public function cancelAudit(int $auditId, string $correlationId = ''): PrivacyAudit
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($auditId, $correlationId): PrivacyAudit {
            $audit = PrivacyAudit::findOrFail($auditId);

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

            $this->logger->info('Privacy audit cancelled', [
                'audit_id' => $audit->id, 'refunded' => $wasPaid, 'correlation_id' => $correlationId,
            ]);

            return $audit;
        });
    }

    public function getAudit(int $auditId): PrivacyAudit
    {
        return PrivacyAudit::findOrFail($auditId);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, PrivacyAudit> */
    public function getClientAudits(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return PrivacyAudit::where('client_id', $clientId)->orderByDesc('created_at')->take($limit)->get();
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
