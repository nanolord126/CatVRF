<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Domains\Freelance\Models\FreelanceProject;
use App\Domains\Freelance\Models\FreelanceProposal;
use App\Domains\Freelance\Events\ProposalAccepted;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * FreelanceService — управление фриланс-проектами и предложениями.
 *
 * Создание проектов, подача и принятие предложений,
 * fraud-check и wallet-интеграция.
 *
 * @package App\Domains\Freelance\Services
 */
final readonly class FreelanceService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    /**
     * Создать фриланс-проект.
     */
    public function createProject(
        int $clientId,
        string $title,
        string $description,
        int $budget,
        string $category,
        string $correlationId = '',
    ): FreelanceProject {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->fraud->check(
            userId: $clientId,
            operationType: 'freelance_project_create',
            amount: $budget,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($clientId, $title, $description, $budget, $category, $correlationId) {
            $project = FreelanceProject::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'correlation_id' => $correlationId,
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description,
                'budget' => $budget,
                'category' => $category,
                'status' => 'open',
                'tags' => ['category' => $category],
            ]);

            $this->audit->log(
                action: 'freelance_project_created',
                subjectType: FreelanceProject::class,
                subjectId: $project->id,
                old: [],
                new: $project->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Freelance project created', [
                'project_id' => $project->id,
                'client_id' => $clientId,
                'budget' => $budget,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Подать предложение от фрилансера.
     */
    public function submitProposal(
        int $projectId,
        int $freelancerId,
        int $price,
        string $coverLetter,
        int $deliveryDays,
        string $correlationId = '',
    ): FreelanceProposal {
        $correlationId = $correlationId ?: (string) Str::uuid();

        $this->fraud->check(
            userId: $freelancerId,
            operationType: 'freelance_proposal_submit',
            amount: $price,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($projectId, $freelancerId, $price, $coverLetter, $deliveryDays, $correlationId) {
            $proposal = FreelanceProposal::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'correlation_id' => $correlationId,
                'project_id' => $projectId,
                'freelancer_id' => $freelancerId,
                'price' => $price,
                'cover_letter' => $coverLetter,
                'delivery_days' => $deliveryDays,
                'status' => 'pending',
                'tags' => [],
            ]);

            $this->audit->log(
                action: 'freelance_proposal_submitted',
                subjectType: FreelanceProposal::class,
                subjectId: $proposal->id,
                old: [],
                new: $proposal->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Freelance proposal submitted', [
                'proposal_id' => $proposal->id,
                'project_id' => $projectId,
                'freelancer_id' => $freelancerId,
                'correlation_id' => $correlationId,
            ]);

            return $proposal;
        });
    }

    /**
     * Принять предложение.
     */
    public function acceptProposal(int $proposalId, string $correlationId = ''): FreelanceProposal
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($proposalId, $correlationId) {
            $proposal = FreelanceProposal::findOrFail($proposalId);

            $proposal->update([
                'status' => 'accepted',
                'correlation_id' => $correlationId,
            ]);

            event(new ProposalAccepted(
                proposal: $proposal,
                correlationId: $correlationId,
            ));

            $this->audit->log(
                action: 'freelance_proposal_accepted',
                subjectType: FreelanceProposal::class,
                subjectId: $proposal->id,
                old: ['status' => 'pending'],
                new: ['status' => 'accepted'],
                correlationId: $correlationId,
            );

            $this->logger->info('Freelance proposal accepted', [
                'proposal_id' => $proposal->id,
                'correlation_id' => $correlationId,
            ]);

            return $proposal;
        });
    }

    /**
     * Получить открытые проекты.
     */
    public function getOpenProjects(): Collection
    {
        return FreelanceProject::where('status', 'open')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Получить проект по ID.
     */
    public function getProject(int $projectId): FreelanceProject
    {
        return FreelanceProject::findOrFail($projectId);
    }
}
