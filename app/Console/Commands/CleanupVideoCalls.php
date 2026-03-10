<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VideoCall;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;
use Exception;

class CleanupVideoCalls extends Command
{
    protected $signature = 'videocall:cleanup {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Удаление записей и файлов звонков старше 1 года (Production 2026)';

    public function handle(): int
    {
        try {
            $correlationId = Str::uuid()->toString();
            $dryRun = $this->option('dry-run');
            $startTime = microtime(true);

            Log::channel('commands')->info('CleanupVideoCalls started', [
                'correlation_id' => $correlationId,
                'dry_run' => $dryRun,
                'timestamp' => now()->toIso8601String(),
            ]);

            $cutoff = Carbon::now()->subYear();
            $videoCalls = VideoCall::where('created_at', '<', $cutoff)->get();

            $this->info("Found {$videoCalls->count()} video calls to clean up...");

            $deletedCount = 0;
            $failedCount = 0;
            $storageFreed = 0;

            foreach ($videoCalls as $call) {
                try {
                    $recordingSize = 0;

                    if ($call->recording_path) {
                        try {
                            $recordingSize = Storage::disk('s3')->size($call->recording_path) ?? 0;
                            
                            if (!$dryRun && $recordingSize > 0) {
                                Storage::disk('s3')->delete($call->recording_path);
                            }
                        } catch (Exception $e) {
                            Log::channel('commands')->warning('Failed to delete S3 recording', [
                                'call_id' => $call->id,
                                'path' => $call->recording_path,
                                'error' => $e->getMessage(),
                                'correlation_id' => $correlationId,
                            ]);
                        }
                    }

                    if (!$dryRun) {
                        $call->delete();
                    }

                    $storageFreed += $recordingSize;
                    $deletedCount++;

                } catch (Throwable $e) {
                    $failedCount++;
                    Log::channel('commands')->error('Failed to cleanup video call', [
                        'call_id' => $call->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                }
            }

            // Логирование
            if (!$dryRun) {
                AuditLog::create([
                    'action' => 'videocall.cleanup_completed',
                    'description' => "Удалены записи видеозвонков старше {$cutoff->toDateString()}",
                    'correlation_id' => $correlationId,
                    'metadata' => [
                        'deleted_calls' => $deletedCount,
                        'failed_calls' => $failedCount,
                        'storage_freed_bytes' => $storageFreed,
                        'storage_freed_mb' => round($storageFreed / (1024 * 1024), 2),
                    ],
                ]);
            }

            $duration = round(microtime(true) - $startTime, 2);
            $storageMb = round($storageFreed / (1024 * 1024), 2);

            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            if ($dryRun) {
                $this->comment("[DRY RUN] Would delete {$deletedCount} calls");
            } else {
                $this->info("✓ Deleted: {$deletedCount} video calls");
            }
            $this->error("✗ Failed: {$failedCount}");
            $this->comment("💾 Storage freed: {$storageMb} MB");
            $this->comment("⏱ Duration: {$duration}s");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            Log::channel('commands')->info('CleanupVideoCalls completed', [
                'correlation_id' => $correlationId,
                'deleted' => $deletedCount,
                'failed' => $failedCount,
                'storage_freed_mb' => $storageMb,
                'duration_seconds' => $duration,
                'dry_run' => $dryRun,
            ]);

            return self::SUCCESS;

        } catch (Throwable $e) {
            Log::channel('commands')->critical('CleanupVideoCalls failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Sentry\captureException($e);

            $this->error("Command failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
