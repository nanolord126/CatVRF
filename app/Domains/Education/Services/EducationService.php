<?php

namespace App\Domains\Education\Services;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\Enrollment;
use App\Domains\Finances\Services\PaymentService;
use App\Domains\Finances\Services\WalletService;
use App\Models\User;
use Illuminate\Support\{Carbon, Facades};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class EducationService
{
    private string $correlationId;
    private ?int $tenantId;

    public function __construct(
        private PaymentService $paymentService,
        private WalletService $walletService
    ) {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Зачисление студента на курс с обработкой платежа.
     */
    public function enrollCourse(Course $course, array $data): Enrollment
    {
        try {
            Log::channel('education')->info('Course enrollment started', [
                'course_id' => $course->id,
                'student_id' => $data['student_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'unknown',
                'correlation_id' => $this->correlationId,
            ]);

            // Валидация данных
            if (empty($data['student_id'])) {
                throw new \InvalidArgumentException("Student ID is required");
            }

            $studentId = (int) $data['student_id'];
            $student = User::findOrFail($studentId);
            $paymentMethod = $data['payment_method'] ?? 'external';

            // Проверка дублирования записи
            $existingEnrollment = Enrollment::where('course_id', $course->id)
                ->where('student_id', $studentId)
                ->first();

            if ($existingEnrollment) {
                Log::info('Student already enrolled', [
                    'course_id' => $course->id,
                    'student_id' => $studentId,
                    'enrollment_id' => $existingEnrollment->id,
                    'correlation_id' => $this->correlationId,
                ]);
                return $existingEnrollment;
            }

            // Создание записи о зачислении
            $enrollment = Enrollment::create([
                'course_id' => $course->id,
                'student_id' => $studentId,
                'amount_paid' => 0.00,
                'is_paid' => false,
                'correlation_id' => $this->correlationId,
                'tenant_id' => $this->tenantId,
                'enrolled_at' => Carbon::now(),
                'progress_percent' => 0,
            ]);

            Log::info('Enrollment record created', [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'student_id' => $studentId,
                'correlation_id' => $this->correlationId,
            ]);

            // Обработка платежа
            $paymentSuccess = false;
            $paymentDetails = [];

            if ($paymentMethod === 'wallet') {
                $paymentSuccess = $this->processWalletPayment($student, $course, $enrollment);
                if ($paymentSuccess) {
                    $paymentDetails = ['method' => 'wallet', 'processed' => true];
                }
            } elseif ($paymentMethod === 'external') {
                // Инициация внешнего платежа
                $paymentDetails = $this->startExternalPayment($enrollment);
                Log::info('External payment initiated', [
                    'enrollment_id' => $enrollment->id,
                    'payment_method' => 'external',
                    'correlation_id' => $this->correlationId,
                ]);
            } else {
                throw new \InvalidArgumentException("Invalid payment method: {$paymentMethod}");
            }

            // Аудит записи о зачислении
            try {
                AuditLog::create([
                    'entity_type' => Enrollment::class,
                    'entity_id' => $enrollment->id,
                    'action' => 'created',
                    'user_id' => Auth::id(),
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                    'changes' => [],
                    'metadata' => [
                        'course_id' => $course->id,
                        'student_id' => $studentId,
                        'payment_method' => $paymentMethod,
                        'payment_success' => $paymentSuccess,
                        'course_price' => $course->price,
                    ],
                ]);
            } catch (Throwable $e) {
                Log::warning('Enrollment audit failed', ['error' => $e->getMessage()]);
            }

            Log::channel('education')->info('Course enrollment completed', [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'student_id' => $studentId,
                'payment_success' => $paymentSuccess,
                'correlation_id' => $this->correlationId,
            ]);

            return $enrollment;
        } catch (Throwable $e) {
            Log::error('Course enrollment failed', [
                'course_id' => $course->id ?? null,
                'student_id' => $data['student_id'] ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Обработка платежа из кошелька студента.
     */
    private function processWalletPayment(User $student, Course $course, Enrollment $enrollment): bool
    {
        try {
            Log::info('Processing wallet payment', [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'correlation_id' => $this->correlationId,
            ]);

            $this->walletService->debit(
                $student,
                $course->price,
                "Enrollment: {$course->title}",
                null,
                [
                    'correlation_id' => $this->correlationId,
                    'course_id' => $course->id,
                ]
            );

            $enrollment->update([
                'is_paid' => true,
                'amount_paid' => $course->price,
                'enrolled_at' => Carbon::now(),
            ]);

            Log::channel('education')->info('Wallet payment processed', [
                'enrollment_id' => $enrollment->id,
                'student_id' => $student->id,
                'amount' => $course->price,
                'correlation_id' => $this->correlationId,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Wallet payment processing failed', [
                'student_id' => $student->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            // Попытка откатить запись
            try {
                $enrollment->delete();
            } catch (Throwable $deleteError) {
                Log::error('Failed to rollback enrollment', ['error' => $deleteError->getMessage()]);
            }

            throw $e;
        }
    }

    /**
     * Инициация внешнего платежа для записи.
     */
    public function startExternalPayment(Enrollment $enrollment): array
    {
        try {
            Log::info('Initiating external payment', [
                'enrollment_id' => $enrollment->id,
                'amount' => $enrollment->course->price,
                'student_id' => $enrollment->student_id,
                'correlation_id' => $this->correlationId,
            ]);

            $paymentResult = $this->paymentService->initPayment([
                'amount' => (float) $enrollment->course->price,
                'order_id' => "EDU-{$enrollment->id}",
                'user_id' => $enrollment->student_id,
                'order_type' => 'course_enrollment',
                'metadata' => [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'correlation_id' => $this->correlationId,
                ],
            ]);

            Log::channel('education')->info('External payment initiated', [
                'enrollment_id' => $enrollment->id,
                'payment_status' => $paymentResult['status'] ?? 'pending',
                'correlation_id' => $this->correlationId,
            ]);

            return $paymentResult;
        } catch (Throwable $e) {
            Log::error('External payment initiation failed', [
                'enrollment_id' => $enrollment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }
}
