<?php

namespace App\Console\Commands;

use App\Models\AiAssistantChat;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResetAiQuotas extends Command
{
    protected $signature = 'ai:reset-quotas {--dry-run : Show what would be reset without actually resetting}';
    protected $description = 'Resets AI request quotas at 00:00 UTC (Production 2026)';

    public function handle(): int
    {
        try {
            $correlationId = Str::uuid()->toString();
            $dryRun = $this->option('dry-run');
            $startTime = microtime(true);

            Log::channel('commands')->info('ResetAiQuotas started', [
                'correlation_id' => $correlationId,
                'dry_run' => $dryRun,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Получаем чаты с активными квотами
            $activeChats = AiAssistantChat::query()
                ->where('request_count', '>', 0)
                ->get();

            $this->info("Processing {$activeChats->count()} AI assistant chats...");

            $resetCount = 0;
            $newQuotaResetDate = now()->startOfDay()->addDay();

            if (!$dryRun) {
                $resetCount = AiAssistantChat::query()
                    ->update([
                        'request_count' => 0,
                        'quota_reset_at' => $newQuotaResetDate,
                    ]);
            } else {
                $resetCount = $activeChats->count();
            }

            // Логирование успешного сброса
            if (!$dryRun) {
                AuditLog::create([
                    'action' => 'ai.quotas_reset',
                    'description' => 'Сброс квот AI ассистента для всех чатов',
                    'correlation_id' => $correlationId,
                    'metadata' => [
                        'chats_reset' => $resetCount,
                        'new_reset_date' => $newQuotaResetDate->toIso8601String(),
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            }

            $duration = round(microtime(true) - $startTime, 2);

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            if ($dryRun) {
                $this->comment("[DRY RUN] Would reset {$resetCount} quota records");
                $this->comment("Next reset: {$newQuotaResetDate->toDateTimeString()}");
            } else {
                $this->info("✓ Quotas reset for {$resetCount} chats");
                $this->comment("Next reset: {$newQuotaResetDate->toDateTimeString()}");
            }
            $this->comment("⏱ Duration: {$duration}s");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            Log::channel('commands')->info('ResetAiQuotas completed', [
                'correlation_id' => $correlationId,
                'chats_reset' => $resetCount,
                'next_reset_date' => $newQuotaResetDate->toIso8601String(),
                'duration_seconds' => $duration,
                'dry_run' => $dryRun,
            ]);

            return self::SUCCESS;

        } catch (Throwable $e) {
            Log::channel('commands')->critical('ResetAiQuotas failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Sentry\captureException($e);

            $this->error("Command failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
