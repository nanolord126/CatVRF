<?php declare(strict_types=1);

namespace App\Domains\Freelance\TranslationServices\Services;

use App\Domains\Freelance\TranslationServices\Models\TranslationJob;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * TranslationServicesService — управление заказами на перевод.
 *
 * Полный цикл: создание, завершение и отмена заказов
 * с fraud-check, wallet-интеграцией и audit-логированием.
 *
 * @package App\Domains\Freelance\TranslationServices\Services
 */
final readonly class TranslationServicesService
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
     * Создать заказ на перевод.
     */
    public function createJob(
        int $translatorId,
        string $languagePair,
        int $wordCount,
        string $deliveryDate,
        string $correlationId = '',
    ): TranslationJob {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) ($this->guard->id() ?? 0);

        $this->fraud->check(
            userId: $userId,
            operationType: 'translation_job_create',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($translatorId, $languagePair, $wordCount, $deliveryDate, $correlationId, $userId) {
            $ratePerWord = 300; // копейки за слово
            $total = $wordCount * $ratePerWord;

            $job = TranslationJob::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'translator_id' => $translatorId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $total,
                'payout_kopecks' => $total - (int) ($total * 0.14),
                'payment_status' => 'pending',
                'language_pair' => $languagePair,
                'word_count' => $wordCount,
                'delivery_date' => $deliveryDate,
                'tags' => ['translation' => true],
            ]);

            $this->audit->log(
                action: 'translation_job_created',
                subjectType: TranslationJob::class,
                subjectId: $job->id,
                old: [],
                new: $job->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Translation job created', [
                'job_id' => $job->id,
                'translator_id' => $translatorId,
                'correlation_id' => $correlationId,
            ]);

            return $job;
        });
    }

    /**
     * Завершить заказ и выплатить переводчику.
     */
    public function completeJob(int $jobId, string $correlationId = ''): TranslationJob
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($jobId, $correlationId) {
            $job = TranslationJob::findOrFail($jobId);

            if ($job->payment_status !== 'completed') {
                throw new \RuntimeException('Payment not completed for this job', 400);
            }

            $job->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $job->translator_id,
                amount: $job->payout_kopecks,
                type: 'freelance_payout',
                correlationId: $correlationId,
                metadata: ['job_id' => $job->id, 'vertical' => 'translation'],
            );

            $this->audit->log(
                action: 'translation_job_completed',
                subjectType: TranslationJob::class,
                subjectId: $job->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Translation job completed', [
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
    public function cancelJob(int $jobId, string $correlationId = ''): TranslationJob
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($jobId, $correlationId) {
            $job = TranslationJob::findOrFail($jobId);

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
                    walletId: $job->client_id,
                    amount: $job->total_kopecks,
                    type: 'freelance_refund',
                    correlationId: $correlationId,
                    metadata: ['job_id' => $job->id, 'reason' => 'job_cancelled'],
                );
            }

            $this->audit->log(
                action: 'translation_job_cancelled',
                subjectType: TranslationJob::class,
                subjectId: $job->id,
                old: ['status' => $oldStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Translation job cancelled', [
                'job_id' => $job->id,
                'correlation_id' => $correlationId,
            ]);

            return $job;
        });
    }

    /**
     * Получить заказ по ID.
     */
    public function getJob(int $jobId): TranslationJob
    {
        return TranslationJob::findOrFail($jobId);
    }

    /**
     * Получить список заказов клиента.
     */
    public function getUserJobs(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return TranslationJob::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
