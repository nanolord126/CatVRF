<?php declare(strict_types=1);

namespace App\Domains\Education\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EducationManagementService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly LearningPathAIService $aiService,
            private readonly WalletService $walletService,
            private readonly FraudControlService $fraudControl,
        ) {}

        /**
         * B2C: Прямая покупка курса пользователем (Direct Enrollment).
         */
        public function enrollUserDirectly(User $user, Course $course, string $correlationId): Enrollment
        {
            Log::channel('audit')->info('Education B2C: Direct enrollment process started', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'correlation_id' => $correlationId,
            ]);

            $this->fraudControl->check($user, 'course_purchase', ['course_id' => $course->id]);

            return DB::transaction(function () use ($user, $course, $correlationId) {
                // 1. Списание баланса (int kopecks)
                // В реальной системе wallet_id может зависеть от tenant_id пользователя
                // $this->walletService->debit($user->wallet_id, $course->price_kopecks, 'B2C Course Enrollment', $correlationId);

                // 2. Генерация AI траектории обучения
                $aiPath = $this->aiService->generatePersonalizedPath($user, $course);

                // 3. Создание Enrollment (зачисление)
                $enrollment = Enrollment::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'status' => 'active',
                    'progress_percent' => 0,
                    'ai_path' => $aiPath,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Education B2C: Enrollment completed', [
                    'enrollment_id' => $enrollment->id,
                    'user_id' => $user->id,
                ]);

                return $enrollment;
            });
        }

        /**
         * B2B: Зачисление сотрудника по корпоративному контракту (Slot Consumption).
         */
        public function enrollUserUnderContract(User $user, CorporateContract $contract, Course $course, string $correlationId): Enrollment
        {
            Log::channel('audit')->info('Education B2B: Contract-based enrollment started', [
                'user_id' => $user->id,
                'contract_id' => $contract->id,
                'correlation_id' => $correlationId,
            ]);

            // 1. Проверка доступности слотов (slots_available > 0)
            if ($contract->slots_available <= 0) {
                Log::channel('audit')->error('Education B2B: No slots available in contract', [
                    'contract_id' => $contract->id,
                    'user_id' => $user->id,
                ]);
                throw new \Exception("Corporate contract slots exceeded for ID: {$contract->uuid}");
            }

            return DB::transaction(function () use ($user, $contract, $course, $correlationId) {
                // 2. Атомарное обновление слотов
                $contract->decrement('slots_available');

                // 3. Генерация AI траектории обучения
                $aiPath = $this->aiService->generatePersonalizedPath($user, $course);

                // 4. Создание Enrollment со ссылкой на контракт
                $enrollment = Enrollment::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'corporate_contract_id' => $contract->id,
                    'status' => 'active',
                    'progress_percent' => 0,
                    'ai_path' => $aiPath,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Education B2B: Contract slot consumed. Enrollment active.', [
                    'enrollment_id' => $enrollment->id,
                    'contract_id' => $contract->id,
                ]);

                return $enrollment;
            });
        }

        /**
         * Создание нового B2B контракта (Agreement Construction).
         */
        public function createB2BContract(array $data, string $correlationId): CorporateContract
        {
            Log::channel('audit')->info('Education B2B: Creating new corporate agreement', [
                'provider_tenant_id' => $data['provider_tenant_id'],
                'client_tenant_id' => $data['client_tenant_id'],
                'correlation_id' => $correlationId,
            ]);

            return DB::transaction(function () use ($data, $correlationId) {
                // Оплата контракта через WalletService (от Client к Provider)
                // $this->walletService->transfer($data['client_wallet_id'], $data['provider_wallet_id'], $data['total_amount_kopecks'], 'B2B Education Contract', $correlationId);

                return CorporateContract::create([
                    'uuid' => (string) Str::uuid(),
                    'provider_tenant_id' => $data['provider_tenant_id'],
                    'client_tenant_id' => $data['client_tenant_id'],
                    'contract_number' => 'EDU-' . Str::upper(Str::random(8)),
                    'slots_total' => $data['slots_total'],
                    'slots_available' => $data['slots_total'],
                    'total_amount_kopecks' => $data['total_amount_kopecks'],
                    'status' => 'active',
                    'signed_at' => now(),
                    'expires_at' => now()->addYear(),
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
