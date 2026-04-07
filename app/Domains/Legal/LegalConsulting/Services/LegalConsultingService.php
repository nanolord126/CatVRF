<?php

declare(strict_types=1);

namespace App\Domains\Legal\LegalConsulting\Services;

use App\Domains\Legal\LegalConsulting\Models\ConsultationCase;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * LegalConsultingService — управление юридическими консультациями.
 *
 * @package CatVRF
 * @version 2026.1
 */
final readonly class LegalConsultingService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService       $wallet,
        private DatabaseManager     $db,
        private LoggerInterface     $logger,
        private Guard               $guard,
    ) {}

    /**
     * Создать консультационный кейс.
     */
    public function createCase(
        int    $firmId,
        string $caseType,
        string $description,
        int    $hoursEstimate,
        string $correlationId = '',
    ): ConsultationCase {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($firmId, $caseType, $description, $hoursEstimate, $correlationId): ConsultationCase {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'legal_consulting',
                amount: 0,
                correlationId: $correlationId,
            );

            $rateKopecks = 600000;
            $total = $rateKopecks * $hoursEstimate;

            $case = ConsultationCase::create([
                'uuid'           => (string) Str::uuid(),
                'tenant_id'      => tenant()->id,
                'firm_id'        => $firmId,
                'client_id'      => $this->guard->id() ?? 0,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'case_type'      => $caseType,
                'description'    => $description,
                'hours_estimate' => $hoursEstimate,
                'tags'           => ['legal_consulting' => true],
            ]);

            $this->logger->info('Consultation case created', [
                'case_id'        => $case->id,
                'correlation_id' => $correlationId,
            ]);

            return $case;
        });
    }

    public function completeCase(int $caseId, string $correlationId = ''): ConsultationCase
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($caseId, $correlationId): ConsultationCase {
            $case = ConsultationCase::findOrFail($caseId);

            if ($case->payment_status !== 'completed') {
                throw new \RuntimeException('Not paid', 400);
            }

            $case->update(['status' => 'completed', 'correlation_id' => $correlationId]);

            $this->wallet->credit(
                walletId: tenant()->id,
                amount: $case->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['case_id' => $case->id],
            );

            $this->logger->info('Consultation case completed', [
                'case_id' => $case->id, 'correlation_id' => $correlationId,
            ]);

            return $case;
        });
    }

    public function cancelCase(int $caseId, string $correlationId = ''): ConsultationCase
    {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($caseId, $correlationId): ConsultationCase {
            $case = ConsultationCase::findOrFail($caseId);

            if ($case->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed case', 400);
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

            $this->logger->info('Consultation case cancelled', [
                'case_id' => $case->id, 'refunded' => $wasPaid, 'correlation_id' => $correlationId,
            ]);

            return $case;
        });
    }

    public function getCase(int $caseId): ConsultationCase
    {
        return ConsultationCase::findOrFail($caseId);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, ConsultationCase> */
    public function getClientCases(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ConsultationCase::where('client_id', $clientId)->orderByDesc('created_at')->take($limit)->get();
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
