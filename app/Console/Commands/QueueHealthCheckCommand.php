<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\QueueMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class QueueHealthCheckCommand extends Command
{
    protected $signature = 'queue:health-check {--alert}';
    protected $description = 'Check queue health and send alerts if needed';

    public function handle(QueueMetricsService $metricsService): int
    {
        $this->info('Checking queue health...');

        $queues = ['supermarket', 'supermarket-high', 'supermarket-low', 'crm-sync'];
        $issues = [];

        foreach ($queues as $queue) {
            $health = $metricsService->getHealthStatus($queue);

            if ($health['health'] !== 'healthy') {
                $issues[] = [
                    'queue' => $queue,
                    'health' => $health['health'],
                    'issues' => $health['issues'],
                    'metrics' => $health['metrics'],
                ];
            }
        }

        if (!empty($issues)) {
            $this->error('Queue health issues detected!');
            
            foreach ($issues as $issue) {
                $this->error("Queue: {$issue['queue']} - {$issue['health']}");
                foreach ($issue['issues'] as $problem) {
                    $this->line("  - {$problem}");
                }
            }

            if ($this->option('alert')) {
                $this->sendAlert($issues);
            }

            Log::warning('Queue health check failed', ['issues' => $issues]);
            return self::FAILURE;
        }

        $this->info('All queues are healthy');
        Log::info('Queue health check passed');
        return self::SUCCESS;
    }

    private function sendAlert(array $issues): void
    {
        try {
            $message = "⚠️ Queue Health Alert\n\n";
            
            foreach ($issues as $issue) {
                $message .= "Queue: {$issue['queue']}\n";
                $message .= "Status: {$issue['health']}\n";
                $message .= "Issues: " . implode(', ', $issue['issues']) . "\n";
                $message .= "Pending: {$issue['metrics']['pending']}\n";
                $message .= "Failed: {$issue['metrics']['failed']}\n\n";
            }

            $adminChatId = config('services.telegram.admin_chat_id');
            
            if ($adminChatId) {
                $telegramService = app(\App\Services\TelegramBotService::class);
                $telegramService->sendMessage($adminChatId, $message);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send queue health alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
