<?php declare(strict_types=1);

namespace App\Domains\Freelance\Copywriting\Services;

use App\Domains\Freelance\Copywriting\Models\CopywritingProject;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CopywritingService — управление копирайтинг-проектами.
 *
 * Отвечает за создание, завершение и отмену проектов
 * с полной интеграцией fraud-check, wallet и audit.
 *
 * @package App\Domains\Freelance\Copywriting\Services
 */
final readonly class CopywritingService
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
     * Создать новый копирайтинг-проект.
     */
    public function createProject(
        int $writerId,
        string $copyType,
        int $wordCount,
        string $dueDate,
        string $correlationId = '',
    ): CopywritingProject {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'copywriting_project_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($writerId, $copyType, $wordCount, $dueDate, $correlationId, $userId) {
            $ratePerWord = 250; // копейки за слово
            $total = $wordCount * $ratePerWord;

            $project = CopywritingProject::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'writer_id' => $writerId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'copy_type' => $copyType,
                'word_count' => $wordCount,
                'due_date' => $dueDate,
                'tags' => ['copywriting' => true],
            ]);

            $this->audit->log(
                action: 'copywriting_project_created',
                subjectType: CopywritingProject::class,
                subjectId: $project->id,
                old: [],
                new: $project->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Copywriting project created', [
                'project_id' => $project->id,
                'writer_id' => $writerId,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Завершить проект и выплатить копирайтеру.
     */
    public function completeProject(int $projectId, string $correlationId = ''): CopywritingProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = CopywritingProject::findOrFail($projectId);

            if ($project->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed for this project', 400);
            }

            $project->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $project->writer_id,
                amount: $project->payout_kopecks,
                type: 'freelance_payout',
                correlationId: $correlationId,
                metadata: ['project_id' => $project->id, 'vertical' => 'copywriting'],
            );

            $this->audit->log(
                action: 'copywriting_project_completed',
                subjectType: CopywritingProject::class,
                subjectId: $project->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Copywriting project completed', [
                'project_id' => $project->id,
                'payout' => $project->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Отменить проект и вернуть средства клиенту.
     */
    public function cancelProject(int $projectId, string $correlationId = ''): CopywritingProject
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($projectId, $correlationId) {
            $project = CopywritingProject::findOrFail($projectId);

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
                action: 'copywriting_project_cancelled',
                subjectType: CopywritingProject::class,
                subjectId: $project->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Copywriting project cancelled', [
                'project_id' => $project->id,
                'correlation_id' => $correlationId,
            ]);

            return $project;
        });
    }

    /**
     * Получить проект по ID.
     */
    public function getProject(int $projectId): CopywritingProject
    {
        return CopywritingProject::findOrFail($projectId);
    }

    /**
     * Получить список проектов клиента.
     */
    public function getUserProjects(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return CopywritingProject::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
