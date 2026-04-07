<?php declare(strict_types=1);

namespace App\Domains\Gardening\GardenServices\Services;

use App\Domains\Gardening\GardenServices\Models\GardenJob;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * GardenServicesService — управление садовыми услугами.
 *
 * Полный цикл: создание, завершение, отмена заказов
 * с fraud-check, wallet-интеграцией и audit-логированием.
 *
 * @package App\Domains\Gardening\GardenServices\Services
 */
final readonly class GardenServicesService
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
     * Создать заказ на садовые услуги.
     */
    public function createJob(
        int $professionalId,
        string $jobDate,
        int $durationHours,
        string $jobType,
        string $correlationId = '',
    ): GardenJob {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'garden_job_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($professionalId, $jobDate, $durationHours, $jobType, $correlationId, $userId) {
            $hourlyRate = 300000; // копейки за час
            $total = $durationHours * $hourlyRate;

            $job = GardenJob::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'professional_id' => $professionalId,
                'customer_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'job_date' => $jobDate,
                'duration_hours' => $durationHours,
                'job_type' => $jobType,
                'tags' => ['garden' => true],
            ]);

            $this->audit->log(
                action: 'garden_job_created',
                subjectType: GardenJob::class,
                subjectId: $job->id,
                old: [],
                new: $job->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Garden job created', [
                'job_id' => $job->id,
                'professional_id' => $professionalId,
                'job_type' => $jobType,
                'correlation_id' => $correlationId,
            ]);

            return $job;
        });
    }

    /**
     * Завершить работу и выплатить специалисту.
     */
    public function completeJob(int $jobId, string $correlationId = ''): GardenJob
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($jobId, $correlationId) {
            $job = GardenJob::findOrFail($jobId);

            if ($job->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed for this job', 400);
            }

            $job->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $job->professional_id,
                amount: $job->payout_kopecks,
                type: 'garden_payout',
                correlationId: $correlationId,
                metadata: ['job_id' => $job->id, 'vertical' => 'gardening'],
            );

            $this->audit->log(
                action: 'garden_job_completed',
                subjectType: GardenJob::class,
                subjectId: $job->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Garden job completed', [
                'job_id' => $job->id,
                'payout' => $job->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return $job;
        });
    }

    /**
     * Отменить заказ и вернуть средства.
     */
    public function cancelJob(int $jobId, string $correlationId = ''): GardenJob
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($jobId, $correlationId) {
            $job = GardenJob::findOrFail($jobId);

            if ($job->status === 'completed') {
                throw new \RuntimeException('Cannot cancel a completed job', 400);
            }

            $oldStatus = $job->status;
            $job->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($job->payment_status === 'completed') {
                $this->wallet->credit(
                    walletId: $job->customer_id,
                    amount: $job->total_kopecks,
                    type: 'garden_refund',
                    correlationId: $correlationId,
                    metadata: ['job_id' => $job->id, 'reason' => 'job_cancelled'],
                );
            }

            $this->audit->log(
                action: 'garden_job_cancelled',
                subjectType: GardenJob::class,
                subjectId: $job->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Garden job cancelled', [
                'job_id' => $job->id,
                'correlation_id' => $correlationId,
            ]);

            return $job;
        });
    }

    /**
     * Получить заказ по ID.
     */
    public function getJob(int $jobId): GardenJob
    {
        return GardenJob::findOrFail($jobId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserJobs(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return GardenJob::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
