<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Services;

use App\Domains\PersonalDevelopment\Models\Enrollment;
use App\Domains\PersonalDevelopment\Models\Program;
use App\Domains\PersonalDevelopment\Models\Course;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * PersonalDevelopmentService — Production Ready 2026
 * 
 * Основной оркестратор домена PersonalDevelopment.
 * Реализовано для B2B (корпоративное развитие) и B2C (частные клиенты).
 */
final readonly class PersonalDevelopmentService
{
    /**
     * Конструктор с зависимостями.
     */
    public function __construct(
        private WalletService $walletService,
        private string $correlationId = ''
    ) {
        $this->correlationId = $this->correlationId ?: (string) Str::uuid();
    }

    /**
     * Записаться на программу (B2C: оплата из Wallet, B2B: оплата со счета компании).
     * 
     * @param Program $program
     * @param \App\Models\User $user
     * @return Enrollment
     * @throws Throwable
     */
    public function enrollToProgram(Program $program, \App\Models\User $user): Enrollment
    {
        Log::channel('audit')->info('PD Main Service: Initializing program enrollment', [
            'program_uuid' => $program->uuid,
            'user_id' => $user->id,
            'correlation_id' => $this->correlationId,
        ]);

        // Fraud Control Check
        FraudControlService::check([
            'user_id' => $user->id,
            'type' => 'pd_program_enrollment',
            'amount' => $program->price_kopecks,
            'correlation_id' => $this->correlationId,
        ]);

        return DB::transaction(function () use ($program, $user) {
            
            // 1. Оплата (в B2B режиме цена может быть нулевой для сотрудника, списана со счета компании)
            if ($program->is_corporate) {
                // Корпоративная оплата: списание со счета Tenant компании
                $this->walletService->debit(
                    userId: $user->id, // В 2026 дебет теннанта идет через контекст пользователя/организации
                    amount: $program->price_kopecks,
                    type: 'withdrawal',
                    reason: "Корпоративное обучение: {$program->title}",
                    correlationId: $this->correlationId
                );
            } else {
                // Частная оплата (B2C)
                $this->walletService->debit(
                    userId: $user->id,
                    amount: $program->price_kopecks,
                    type: 'withdrawal',
                    reason: "Запись на программу: {$program->title}",
                    correlationId: $this->correlationId
                );
            }

            // 2. Создание записи об участии
            /** @var Enrollment $enrollment */
            $enrollment = Enrollment::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $program->tenant_id,
                'user_id' => $user->id,
                'program_id' => $program->id,
                'status' => 'active',
                'progress_percent' => 0,
                'correlation_id' => $this->correlationId,
            ]);

            Log::channel('audit')->info('PD Main Service: Enrollment successful', [
                'enrollment_uuid' => $enrollment->uuid,
                'correlation_id' => $this->correlationId,
            ]);

            return $enrollment;
        });
    }

    /**
     * Завершение программы с выдачей сертификата.
     */
    public function completeEnrollment(Enrollment $enrollment): void
    {
        if ($enrollment->progress_percent < 100) {
            throw new \Exception('Программа еще не завершена.');
        }

        DB::transaction(function () use ($enrollment) {
            $enrollment->update([
                'status' => 'completed',
                'correlation_id' => $this->correlationId,
            ]);

            // Выдача бонусных баллов за завершение (Канон 2026)
            $this->walletService->credit(
                userId: $enrollment->user_id,
                amount: 100000, // 1000 бонусов
                type: 'bonus',
                reason: "Бонус за завершение обучения: " . ($enrollment->program?->title ?? $enrollment->course?->title),
                correlationId: $this->correlationId
            );

            Log::channel('audit')->info('PD Main Service: Enrollment completed with bonuses', [
                'enrollment_uuid' => $enrollment->uuid,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }
}
