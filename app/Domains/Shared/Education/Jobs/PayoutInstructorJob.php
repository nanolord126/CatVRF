<?php declare(strict_types=1);

namespace App\Domains\Education\Jobs;

use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\Teacher;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

final class PayoutInstructorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $backoff = [60, 300, 900];
    public int $timeout = 120;
    public int $tries = 3;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int $enrollmentId,
        private readonly string $correlationId,
        private readonly int $payoutAmountKopecks,
    ) {
        $this->onQueue('default');
    }

    /**
     * Основная логика выплаты инструктору.
     */
    public function handle(
        WalletService $walletService,
        FraudControlService $fraud,
        AuditService $audit,
        LoggerInterface $logger,
    ): void {
        $correlationId = $this->correlationId;
        $enrollment = Enrollment::findOrFail($this->enrollmentId);
        $course = $enrollment->course;
        $teacher = Teacher::findOrFail($course->teacher_id);

        $fraud->check(
            userId: $teacher->user_id ?? $teacher->id,
            operationType: 'instructor_payout',
            amount: $this->payoutAmountKopecks,
            correlationId: $correlationId,
        );

        try {
            $walletService->credit(
                walletId: $teacher->wallet_id ?? $teacher->tenant_id ?? tenant()->id,
                amount: $this->payoutAmountKopecks,
                reason: 'instructor_payout',
                correlationId: $correlationId,
            );

            $audit->record(
                action: 'instructor_payout_completed',
                subjectType: Enrollment::class,
                subjectId: $enrollment->id,
                oldValues: [],
                newValues: [
                    'teacher_id' => $teacher->id,
                    'amount' => $this->payoutAmountKopecks,
                ],
                correlationId: $correlationId,
            );

            $logger->info('Instructor payout completed', [
                'teacher_id' => $teacher->id,
                'enrollment_id' => $enrollment->id,
                'amount' => $this->payoutAmountKopecks,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $logger->error('Instructor payout failed', [
                'enrollment_id' => $this->enrollmentId,
                'amount' => $this->payoutAmountKopecks,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Теги для очереди.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['education', 'payout', $this->correlationId];
    }
    public function failed(\Throwable $exception): void
    {
        $this->logger->error('Instructor payout job failed', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}