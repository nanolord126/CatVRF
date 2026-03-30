<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private WalletService $walletService,
            private FraudControlService $fraudControl,
        ) {}

        /**
         * Зачисление студента на курс (B2C: Оплата модулем или полная сумма)
         */
        public function enrollStudent(int $userId, int $courseId, string $type = 'full'): Enrollment
        {
            $correlationId = (string) Str::uuid();

            return DB::transaction(function () use ($userId, $courseId, $type, $correlationId) {
                $course = Course::findOrFail($courseId);

                // 1. Проверка на фрод и права
                $this->fraudControl->checkOperation('enroll_student', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'correlation_id' => $correlationId
                ]);

                // 2. Проверка дубликатов
                $existing = Enrollment::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->first();

                if ($existing && $existing->isActive()) {
                    throw new \RuntimeException('Вы уже зачислены на данный курс и имеете активный доступ.');
                }

                // 3. Оплата (Withdraw from Wallet) - В копейках
                $price = ($type === 'full') ? $course->price : ($course->subscription_price ?? $course->price);

                if ($price > 0) {
                    $this->walletService->debit(
                        userId: $userId,
                        amount: $price,
                        reason: "Оплата курса: {$course->title} ({$type})",
                        correlationId: $correlationId
                    );
                }

                // 4. Создание записи зачисления
                $enrollment = Enrollment::updateOrCreate(
                    ['user_id' => $userId, 'course_id' => $courseId],
                    [
                        'tenant_id' => tenant()->id,
                        'type' => $type,
                        'status' => 'active',
                        'expires_at' => ($type === 'subscription') ? now()->addMonth() : null,
                        'correlation_id' => $correlationId,
                    ]
                );

                // 5. Логирование и событие
                Log::channel('audit')->info('Student enrolled in course', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'enrollment_id' => $enrollment->id,
                    'correlation_id' => $correlationId,
                ]);

                event(new StudentEnrolled($enrollment, $correlationId));

                return $enrollment;
            });
        }

        /**
         * Массовое зачисление сотрудников (B2B)
         */
        public function enrollCorporateGroup(int $businessGroupId, int $courseId, array $userIds): void
        {
            $correlationId = (string) Str::uuid();

            DB::transaction(function () use ($businessGroupId, $courseId, $userIds, $correlationId) {
                $course = Course::findOrFail($courseId);

                foreach ($userIds as $userId) {
                    $this->enrollStudent($userId, $courseId, 'b2b');
                }

                Log::channel('audit')->info('Corporate group enrolled in course', [
                    'business_group_id' => $businessGroupId,
                    'course_id' => $courseId,
                    'students_count' => count($userIds),
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Приостановление доступа студента
         */
        public function suspendAccess(int $enrollmentId, string $reason): void
        {
            $correlationId = (string) Str::uuid();
            $enrollment = Enrollment::findOrFail($enrollmentId);

            $enrollment->update([
                'status' => 'suspended',
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->warning('Student enrollment suspended', [
                'enrollment_id' => $enrollmentId,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        }
}
