<?php

declare(strict_types=1);

namespace App\Domains\Legal\ConflictResolution\Services;

use App\Domains\Legal\ConflictResolution\Models\MediationCase;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * ConflictResolutionService — управление медиациями и спорами.
 *
 * @version 2026.1
 */
final readonly class ConflictResolutionService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly WalletService $wallet,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать кейс медиации.
     */
    public function createCase(
        int $specialistId,
        string $caseType,
        string $description,
        string $correlationId = '',
    ): MediationCase {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($specialistId, $caseType, $description, $correlationId): MediationCase {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'mediation_case',
                amount: 0,
                correlationId: $correlationId,
            );

            $rateKopecks = 500000;

            $case = MediationCase::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'specialist_id'  => $specialistId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $rateKopecks,
                'payout_kopecks' => $rateKopecks - (int) ($rateKopecks * 0.14),
                'payment_status' => 'pending',
                'case_type'      => $caseType,
                'description'    => $description,
                'tags'           => ['mediation' => true],
            ]);

            $this->logger->$this->logger->info('Mediation case created', [
                'case_id'        => $case->id,
                'correlation_id' => $correlationId,
            ]);

            return $case;
        });
    }

    public function resolveCase(int $caseId, string $resolution, string $correlationId = ''): MediationCase
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($caseId, $resolution, $correlationId): MediationCase {
            $case = MediationCase::findOrFail($caseId);

            if ($case->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $case->update([
                'status'         => 'resolved',
                'resolution'     => $resolution,
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $case->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['case_id' => $case->id],
            );

            $this->logger->$this->logger->info('Mediation case resolved', [
                'case_id' => $case->id, 'correlation_id' => $correlationId,
            ]);

            return $case;
        });
    }

    public function cancelCase(int $caseId, string $correlationId = ''): MediationCase
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($caseId, $correlationId): MediationCase {
            $case = MediationCase::findOrFail($caseId);

            if ($case->status === 'resolved') {
                throw new \RuntimeException('Cannot cancel resolved case', 400);
            }

            $wasPaid = $case->payment_status === 'completed';

            $case->update([
                'status'         => 'cancelled',
                'payment_status' => $wasPaid ? 'refunded' : $case->payment_status,
                'correlation_id' => $correlationId,
            ]);

            if ($wasPaid) {
                $this->wallet->credit(
                    walletId: tenant()->id,
                    amount: $case->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['case_id' => $case->id],
                );
            }

            $this->logger->$this->logger->info('Mediation case cancelled', [
                'case_id' => $case->id, 'refunded' => $wasPaid, 'correlation_id' => $correlationId,
            ]);

            return $case;
        });
    }

    public function getCase(int $caseId): MediationCase
    {
        return MediationCase::findOrFail($caseId);
    }

    /** @return Collection<int, MediationCase> */
    public function getClientCases(int $clientId, int $limit = 10): Collection
    {
        return MediationCase::where('client_id', $clientId)->orderByDesc('created_at')->take($limit)->get();
    }

    public function __toString(): string
    {
        return self::class;
    }

    /** @return array<string, mixed> */
    public function toDebugArray(): array
    {
        return ['class' => self::class, 'timestamp' => CarbonImmutable::now()->toIso8601String()];
    }
}
