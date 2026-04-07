<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;


use Carbon\Carbon;
use App\Domains\Freelance\Models\FreelanceContract;
use App\Domains\Freelance\Models\FreelanceProposal;
use App\Domains\Freelance\Events\PaymentMilestoneReleased;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ContractService — управление фриланс-контрактами.
 *
 * Создание, активация, завершение контрактов,
 * управление milestones и выплатами.
 * Все мутации через DB::transaction + fraud-check.
 *
 * @package App\Domains\Freelance\Services
 */
final readonly class ContractService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Создать контракт из принятого предложения.
     */
    public function createContract(
        FreelanceProposal $proposal,
        int $milestoneCount,
        string $correlationId = '',
    ): FreelanceContract {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->fraud->check(
            userId: $proposal->client_id,
            operationType: 'freelance_contract_create',
            amount: $proposal->price,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($proposal, $milestoneCount, $correlationId) {
            $contract = FreelanceContract::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'correlation_id' => $correlationId,
                'proposal_id' => $proposal->id,
                'client_id' => $proposal->client_id,
                'freelancer_id' => $proposal->freelancer_id,
                'title' => $proposal->title,
                'total_price' => $proposal->price,
                'milestone_count' => $milestoneCount,
                'status' => 'active',
                'tags' => ['source' => 'proposal'],
            ]);

            $this->audit->log(
                action: 'freelance_contract_created',
                subjectType: FreelanceContract::class,
                subjectId: $contract->id,
                old: [],
                new: $contract->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Freelance contract created', [
                'contract_id' => $contract->id,
                'proposal_id' => $proposal->id,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    /**
     * Завершить контракт и финализировать выплаты.
     */
    public function completeContract(int $contractId, string $correlationId = ''): FreelanceContract
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($contractId, $correlationId) {
            $contract = FreelanceContract::findOrFail($contractId);

            if ($contract->status !== 'active') {
                throw new \RuntimeException('Contract is not active', 400);
            }

            $contract->update([
                'status' => 'completed',
                'completed_at' => Carbon::now(),
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                action: 'freelance_contract_completed',
                subjectType: FreelanceContract::class,
                subjectId: $contract->id,
                old: ['status' => 'active'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Freelance contract completed', [
                'contract_id' => $contract->id,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    /**
     * Релиз оплаты за milestone.
     */
    public function releaseMilestonePayment(
        int $contractId,
        int $milestoneNumber,
        string $correlationId = '',
    ): FreelanceContract {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($contractId, $milestoneNumber, $correlationId) {
            $contract = FreelanceContract::findOrFail($contractId);

            $milestoneAmount = (int) ($contract->total_price / $contract->milestone_count);

            $this->fraud->check(
                userId: $contract->client_id,
                operationType: 'freelance_milestone_release',
                amount: $milestoneAmount,
                correlationId: $correlationId,
            );

            event(new PaymentMilestoneReleased(
                contract: $contract,
                amount: $milestoneAmount,
                milestoneNumber: $milestoneNumber,
                correlationId: $correlationId,
            ));

            $this->audit->log(
                action: 'freelance_milestone_released',
                subjectType: FreelanceContract::class,
                subjectId: $contract->id,
                old: [],
                new: [
                    'milestone' => $milestoneNumber,
                    'amount' => $milestoneAmount,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('Milestone payment released', [
                'contract_id' => $contract->id,
                'milestone' => $milestoneNumber,
                'amount' => $milestoneAmount,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    /**
     * Получить контракт по ID.
     */
    public function getContract(int $contractId): FreelanceContract
    {
        return FreelanceContract::findOrFail($contractId);
    }
}
