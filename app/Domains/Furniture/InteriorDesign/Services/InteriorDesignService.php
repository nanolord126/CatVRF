<?php declare(strict_types=1);

namespace App\Domains\Furniture\InteriorDesign\Services;

use App\Domains\Furniture\InteriorDesign\Models\DesignProject;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * InteriorDesignService — управление проектами интерьерного дизайна.
 *
 * Полный цикл: создание, завершение, отмена проектов
 * с fraud-check, wallet-интеграцией, 3D-визуализацией и audit-логированием.
 *
 * @package App\Domains\Furniture\InteriorDesign\Services
 */
final readonly class InteriorDesignService
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
     * Создать проект дизайна интерьера.
     */
    public function createProject(
        int $designerId,
        string $style,
        float $spaceSqm,
        string $dueDate,
        string $correlationId = '',
    ): DesignProject {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'interior_design_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($designerId, $style, $spaceSqm, $dueDate, $correlationId, $userId) {
            $pricePerSqm = 150000; // копейки за м²
            $total = (int) ($spaceSqm * $pricePerSqm);

            $project = DesignProject::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'designer_id' => $designerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'style' => $style,
                'space_sqm' => $spaceSqm,
                'due_date' => $dueDate,
                'tags' => ['interior' => true],
            ]);

            $this->audit->log(
                action: 'interior_design_project_created',
                subjectType: DesignProject::class,
                subjectId: $project->id,
                old: [],
                new: $project->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Interior design project created', [
                'project_id' => $project->id,
                'designer_id' => $designerId,
                'style' => $style,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Завершить проект и выплатить дизайнеру.
     */
    public function completeProject(int $projectId, string $correlationId = ''): DesignProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = DesignProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed for this project', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $project->designer_id,
                amount: $project->payout_kopecks,
                type: 'design_payout',
                correlationId: $correlationId,
                metadata: ['project_id' => $project->id, 'vertical' => 'interior_design'],
            );

            $this->audit->log(
                action: 'interior_design_project_completed',
                subjectType: DesignProject::class,
                subjectId: $project->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Interior design project completed', [
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
    public function cancelProject(int $projectId, string $correlationId = ''): DesignProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = DesignProject::findOrFail($projectId);

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
                    type: 'design_refund',
                    correlationId: $correlationId,
                    metadata: ['project_id' => $project->id, 'reason' => 'project_cancelled'],
                );
            }

            $this->audit->log(
                action: 'interior_design_project_cancelled',
                subjectType: DesignProject::class,
                subjectId: $project->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Interior design project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Получить проект по ID.
     */
    public function getProject(int $projectId): DesignProject
    {
        return DesignProject::findOrFail($projectId);
    }

    /**
     * Получить список проектов клиента.
     */
    public function getUserProjects(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return DesignProject::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
