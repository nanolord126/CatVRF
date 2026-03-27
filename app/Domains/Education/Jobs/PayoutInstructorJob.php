<?php

declare(strict_types=1);

namespace App\Domains\Education\Jobs;

use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\Teacher;
use App\Domains\Education\Models\Course;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

/**
 * КАНОН 2026: PayoutInstructorJob (Education).
 * Осуществляет выплату преподавателю после того, как студент завершил курс или модуль.
 * Использует WalletService для работы с балансом.
 */
final class PayoutInstructorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 min

    public function __construct(
        public readonly int $enrollmentId,
        public readonly string $correlationId,
        private readonly int $payoutAmountKopecks, // Фиксированная сумма выплаты или доля
    ) {}

    /**
     * Основная логика выплаты
     */
    public function handle(WalletService $walletService, FraudControlService $fraudControl): void
    {
        $correlationId = $this->correlationId;
        $enrollment = Enrollment::findOrFail($this->enrollmentId);
        $course = $enrollment->course;
        $teacher = Teacher::findOrFail($course->teacher_id);

        // 1. Предварительная фрод-проверка
        $fraudControl->checkOperation('instructor_payout', [
            'enrollment_id' => $enrollment->id,
            'teacher_id' => $teacher->id,
            'amount' => $this->payoutAmountKopecks,
            'correlation_id' => $correlationId,
        ]);

        // 2. Выполнение выплаты (WalletService::credit - пополнение кошелька)
        try {
            // Выплата на кошелек преподавателя (через wallet_id если он есть, или через бизнес-кошелек тенанта)
            $walletData = [
                'type' => 'payout',
                'amount' => $this->payoutAmountKopecks,
                'status' => 'completed',
                'correlation_id' => $correlationId,
                'business_group_id' => $teacher->business_group_id,
            ];

            // credit() увеличивает баланс, это зачисление вознаграждения
            $walletService->credit($walletData);

            // 3. Логирование аудита
            Log::channel('audit')->info('Instructor payout completed', [
                'teacher_id' => $teacher->id,
                'course_id' => $course->id,
                'amount_kopecks' => $this->payoutAmountKopecks,
                'enrollment_id' => $enrollment->id,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            // Ошибка транзакции или кошелька
            Log::channel('audit')->error('Failed to payout instructor', [
                'teacher_id' => $teacher->id,
                'enrollment_id' => $enrollment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e; // Для ретрая в очереди
        }
    }

    /**
     * Теги для очереди
     */
    public function tags(): array
    {
        return ['education', 'payout', $this->correlationId];
    }
}
