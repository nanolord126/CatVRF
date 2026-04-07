<?php declare(strict_types=1);

namespace App\Domains\Freelance\SoftwareDevelopment\Services;

use App\Domains\Freelance\SoftwareDevelopment\Models\SoftwareProject;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * SoftwareDevelopmentService — управление проектами разработки ПО.
 *
 * Полный цикл: создание, завершение, отмена проектов
 * с fraud-check, wallet-интеграцией и audit-логированием.
 *
 * @package App\Domains\Freelance\SoftwareDevelopment\Services
 */
final readonly class SoftwareDevelopmentService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать проект разработки.
     */
    public function createProject(
        int $developerId,
        string $projectType,
        int $developmentHours,
        string $dueDate,
        string $correlationId = '',
    ): SoftwareProject {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'software_project_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($developerId, $projectType, $developmentHours, $dueDate, $correlationId, $userId) {
            $hourlyRate = 500000; // копейки за час
            $total = $developmentHours * $hourlyRate;

            $project = SoftwareProject::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'developer_id' => $developerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'project_type' => $projectType,
                'development_hours' => $developmentHours,
                'due_date' => $dueDate,
                'tags' => ['software' => true],
            ]);

            $this->audit->log(
                action: 'software_project_created',
                subjectType: SoftwareProject::class,
                subjectId: $project->id,
                old: [],
                new: $project->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Software project created', [
                'project_id' => $project->id,
                'developer_id' => $developerId,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Завершить проект и выплатить разработчику.
     */
    public function completeProject(int $projectId, string $correlationId = ''): SoftwareProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = SoftwareProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed for this project', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $project->developer_id,
                amount: $project->payout_kopecks,
                type: 'freelance_payout',
                correlationId: $correlationId,
                metadata: ['project_id' => $project->id, 'vertical' => 'software_dev'],
            );

            $this->audit->log(
                action: 'software_project_completed',
                subjectType: SoftwareProject::class,
                subjectId: $project->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Software project completed', [
                'project_id' => $project->id,
                'payout' => $project->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Отменить проект и вернуть средства.
     */
    public function cancelProject(int $projectId, string $correlationId = ''): SoftwareProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = SoftwareProject::findOrFail($projectId);

            if ($project->status === 'completed') {
                throw new \RuntimeException('Cannot cancel a completed project', 400);
            }

            $oldStatus = $project->status;
            $project->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($project->payment_status === 'completed') {
                $this->wallet->credit(
                    walletId: $project->client_id,
                    amount: $project->total_kopecks,
                    type: 'freelance_refund',
                    correlationId: $correlationId,
                    metadata: ['project_id' => $project->id, 'reason' => 'project_cancelled'],
                );
            }

            $this->audit->log(
                action: 'software_project_cancelled',
                subjectType: SoftwareProject::class,
                subjectId: $project->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Software project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Получить проект по ID.
     */
    public function getProject(int $projectId): SoftwareProject
    {
        return SoftwareProject::findOrFail($projectId);
    }

    /**
     * Получить список проектов клиента.
     */
    public function getUserProjects(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return SoftwareProject::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
