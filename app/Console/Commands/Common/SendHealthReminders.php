<?php

namespace App\Console\Commands\Common;

use Illuminate\Console\Command;
use App\Models\Common\HealthRecommendation;
use App\Models\AuditLog;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SendHealthReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:send-reminders {--tenant= : Tenant ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send PUSH/Web notifications for daily and overdue health tasks (Production 2026)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = $this->option('tenant');

            Log::channel('commands')->info('SendHealthReminders started', [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'timestamp' => now()->toIso8601String(),
            ]);

            $startTime = microtime(true);
            $processedCount = 0;
            $failedCount = 0;

            // Получаем задачи для отправки напоминаний
            $query = HealthRecommendation::where('is_completed', false)
                ->whereDate('next_due_date', '<=', now())
                ->with('owner');

            // Multi-tenant scoping (если указан конкретный тенант)
            if ($tenantId) {
                $query->whereHas('owner', fn ($q) => $q->where('tenant_id', $tenantId));
            }

            $todayTasks = $query->get();

            $this->info("Processing {$todayTasks->count()} health recommendations...");

            foreach ($todayTasks as $task) {
                try {
                    $user = $task->owner;

                    if (!$user) {
                        $failedCount++;
                        Log::channel('commands')->warning('Health task has no owner', [
                            'task_id' => $task->id,
                            'correlation_id' => $correlationId,
                        ]);
                        continue;
                    }

                    // Проверка прав доступа
                    if (!$this->canSendToUser($user, $tenantId)) {
                        $failedCount++;
                        continue;
                    }

                    // Формируем сообщение
                    $message = "Напоминание: {$task->title}";
                    if ($task->target_type === 'ANIMAL') {
                        $message .= " (Нужно для вашего питомца 🐾)";
                    }

                    // Отправляем Filament уведомление
                    Notification::make()
                        ->title('Чеклист Здоровья: Пора выполнить задачу!')
                        ->body($message)
                        ->icon('heroicon-o-heart')
                        ->iconColor('danger')
                        ->actions([
                            Action::make('view')
                                ->label('Открыть Чеклист')
                                ->url(fn () => route('filament.tenant.pages.personal-checklist'))
                                ->button(),
                        ])
                        ->sendToDatabase($user);

                    // Логирование успешной отправки
                    AuditLog::create([
                        'user_id' => $user->id,
                        'action' => 'health.reminder_sent',
                        'description' => "Отправлено напоминание о задаче здоровья: {$task->title}",
                        'model_type' => 'HealthRecommendation',
                        'model_id' => $task->id,
                        'correlation_id' => $correlationId,
                        'metadata' => [
                            'task_title' => $task->title,
                            'target_type' => $task->target_type,
                            'due_date' => $task->next_due_date->toIso8601String(),
                        ],
                    ]);

                    $processedCount++;
                    $this->line("✓ Notification sent to User #{$user->id} for task: {$task->title}");

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::channel('commands')->error('Failed to send health reminder', [
                        'task_id' => $task->id,
                        'user_id' => $task->owner_id ?? null,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);

                    // Отправляем в Sentry для мониторинга
                    \Sentry\captureException($e);

                    $this->error("✗ Failed to send reminder for task #{$task->id}: {$e->getMessage()}");
                }
            }

            // Финальное логирование
            $duration = round(microtime(true) - $startTime, 2);
            Log::channel('commands')->info('SendHealthReminders completed', [
                'correlation_id' => $correlationId,
                'processed' => $processedCount,
                'failed' => $failedCount,
                'total' => $todayTasks->count(),
                'duration_seconds' => $duration,
                'timestamp' => now()->toIso8601String(),
            ]);

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✓ Completed: {$processedCount} reminders sent");
            $this->error("✗ Failed: {$failedCount} reminders failed");
            $this->comment("⏱ Duration: {$duration}s");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            Log::channel('commands')->critical('SendHealthReminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Sentry\captureException($e);

            $this->error("Command failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Проверка прав на отправку уведомления пользователю.
     *
     * @param User $user
     * @param string|null $tenantId
     * @return bool
     */
    private function canSendToUser(User $user, ?string $tenantId): bool
    {
        // Если указан конкретный тенант, проверяем принадлежность
        if ($tenantId && $user->tenant_id !== $tenantId) {
            return false;
        }

        // Проверяем активность пользователя
        if (!$user->email_verified_at) {
            Log::channel('commands')->warning('User email not verified', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        return true;
    }
}
