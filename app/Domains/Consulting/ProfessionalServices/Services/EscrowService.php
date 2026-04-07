<?php declare(strict_types=1);

namespace App\Domains\Consulting\ProfessionalServices\Services;

use App\Domains\Consulting\ProfessionalServices\Models\Contract;
use App\Domains\Consulting\ProfessionalServices\Models\Milestone;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EscrowService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    /**
     * Создание контракта с депонированием средств (Hold).
     */
    public function openContract(
        int $clientId,
        int $providerId,
        int $totalAmount,
        array $milestones,
        string $correlationId = '',
    ): Contract {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        if ($this->rateLimiter->tooManyAttempts("escrow:open:{$clientId}", 3)) {
            throw new \RuntimeException('Too many contract open attempts', 429);
        }

        $this->rateLimiter->hit("escrow:open:{$clientId}", 3600);

        return $this->db->transaction(function () use ($clientId, $providerId, $totalAmount, $milestones, $correlationId): Contract {
            $fraudResult = $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'escrow_open',
                amount: $totalAmount,
                correlationId: $correlationId,
            );

            if ($fraudResult['decision'] === 'block') {
                $this->logger->error('Escrow security block', [
                    'client_id' => $clientId,
                    'score' => $fraudResult['score'] ?? 0,
                    'correlation_id' => $correlationId,
                ]);
                throw new \RuntimeException('Contract blocked by compliance', 403);
            }

            $contract = Contract::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'client_id' => $clientId,
                'provider_id' => $providerId,
                'total_amount_kopecks' => $totalAmount,
                'status' => 'active',
                'correlation_id' => $correlationId,
            ]);

            foreach ($milestones as $mData) {
                Milestone::create([
                    'contract_id' => $contract->id,
                    'title' => $mData['title'],
                    'amount_kopecks' => $mData['amount'],
                    'status' => 'pending',
                ]);
            }

            $this->wallet->credit(
                walletId: (int) tenant()->id,
                amount: $totalAmount,
                reason: 'escrow_hold',
                correlationId: $correlationId,
            );

            $this->logger->info('Escrow contract opened', [
                'contract_id' => $contract->id,
                'total_amount' => $totalAmount,
                'milestones_count' => count($milestones),
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    /**
     * Завершение этапа (milestone) — выплата исполнителю.
     */
    public function completeMilestone(
        int $milestoneId,
        string $correlationId = '',
    ): Milestone {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($milestoneId, $correlationId): Milestone {
            $milestone = Milestone::findOrFail($milestoneId);
            $contract = Contract::findOrFail($milestone->contract_id);

            if ($milestone->status !== 'pending') {
                throw new \RuntimeException('Milestone already processed', 400);
            }

            $milestone->update([
                'status' => 'completed',
            ]);

            $providerPayout = $milestone->amount_kopecks - (int) ($milestone->amount_kopecks * 0.14);

            $this->wallet->credit(
                walletId: (int) tenant()->id,
                amount: $providerPayout,
                reason: 'consulting_payout',
                correlationId: $correlationId,
            );

            $this->logger->info('Escrow milestone completed', [
                'milestone_id' => $milestone->id,
                'contract_id' => $contract->id,
                'payout' => $providerPayout,
                'correlation_id' => $correlationId,
            ]);

            return $milestone;
        });
    }

    /**
     * Отмена контракта — возврат средств клиенту.
     */
    public function cancelContract(
        int $contractId,
        string $correlationId = '',
    ): Contract {
        $correlationId = $correlationId !== '' ? $correlationId : (string) Str::uuid();

        return $this->db->transaction(function () use ($contractId, $correlationId): Contract {
            $contract = Contract::findOrFail($contractId);

            if ($contract->status !== 'active') {
                throw new \RuntimeException('Contract is not active', 400);
            }

            $contract->update([
                'status' => 'cancelled',
            ]);

            $pendingTotal = Milestone::where('contract_id', $contractId)
                ->where('status', 'pending')
                ->sum('amount_kopecks');

            if ($pendingTotal > 0) {
                $this->wallet->credit(
                    walletId: (int) tenant()->id,
                    amount: (int) $pendingTotal,
                    reason: 'consulting_refund',
                    correlationId: $correlationId,
                );
            }

            Milestone::where('contract_id', $contractId)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $this->logger->info('Escrow contract cancelled', [
                'contract_id' => $contract->id,
                'refunded' => $pendingTotal,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    public function getContract(int $contractId): Contract
    {
        return Contract::findOrFail($contractId);
    }
}
