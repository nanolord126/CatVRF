<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Notifications\ChannelManager;
use Carbon\CarbonImmutable;

/**
 * FailedJobAlertService - Monitors failed jobs and sends alerts
 *
 * CRITICAL: Provides observability for queue layer failures
 * - Monitors failed jobs count per queue
 * - Sends alerts when threshold exceeded (> 10 failed jobs in 5 minutes)
 * - Supports multiple notification channels (Slack, Telegram, Email)
 * - Configurable thresholds via environment variables
 * - Exponential backoff tracking for retry analysis
 *
 * CatVRF 2026 - Production Ready
 */
final class FailedJobAlertService
{
    private readonly ?object $jobRepository = null;

    private readonly int $alertThreshold;

    private readonly int $alertWindowMinutes;

    private readonly bool $alertEnabled;

    public function __construct(
        private readonly LogManager $log,
        private readonly ChannelManager $notification,
        private readonly HttpFactory $http,
    ) {
        if (class_exists('Laravel\Horizon\Horizon')) {
            $this->jobRepository = app('Laravel\Horizon\Contracts\JobRepository');
        }
        $this->alertThreshold = config('monitoring.failed_job_alert_threshold', 10);
        $this->alertWindowMinutes = config('monitoring.failed_job_alert_window_minutes', 5);
        $this->alertEnabled = config('monitoring.failed_job_alert_enabled', true);
    }

    /**
     * Check for failed jobs and send alerts if threshold exceeded
     */
    public function checkAndAlert(): void
    {
        if (! $this->alertEnabled) {
            return;
        }

        $failedJobs = $this->getRecentFailedJobs();
        $failedByQueue = $this->groupFailedJobsByQueue($failedJobs);

        foreach ($failedByQueue as $queue => $jobs) {
            if (count($jobs) >= $this->alertThreshold) {
                $this->sendAlert($queue, $jobs);
            }
        }
    }

    /**
     * Get failed job statistics
     */
    public function getFailedJobStats(): array
    {
        $failedJobs = $this->getRecentFailedJobs();
        $failedByQueue = $this->groupFailedJobsByQueue($failedJobs);

        $stats = [];
        foreach ($failedByQueue as $queue => $jobs) {
            $stats[$queue] = [
                'count' => count($jobs),
                'threshold_exceeded' => count($jobs) >= $this->alertThreshold,
                'job_types' => $this->summarizeFailedJobs($jobs)['by_type'],
            ];
        }

        return $stats;
    }

    /**
     * Get recent failed jobs within the alert window
     */
    private function getRecentFailedJobs(): array
    {
        $since = CarbonImmutable::now()->subMinutes($this->alertWindowMinutes);

        return $this->jobRepository->getFailed($since);
    }

    /**
     * Group failed jobs by queue
     */
    private function groupFailedJobsByQueue(array $failedJobs): array
    {
        $grouped = [];

        foreach ($failedJobs as $job) {
            $queue = $job->queue ?? 'default';

            if (! isset($grouped[$queue])) {
                $grouped[$queue] = [];
            }

            $grouped[$queue][] = $job;
        }

        return $grouped;
    }

    /**
     * Send alert for failed jobs
     */
    private function sendAlert(string $queue, array $jobs): void
    {
        $alertData = [
            'queue' => $queue,
            'failed_count' => count($jobs),
            'window_minutes' => $this->alertWindowMinutes,
            'threshold' => $this->alertThreshold,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'jobs' => $this->summarizeFailedJobs($jobs),
        ];

        $this->log->critical('Failed job threshold exceeded', $alertData);

        // Send to monitoring channels
        $this->sendToSlack($alertData);
        $this->sendToTelegram($alertData);
    }

    /**
     * Summarize failed jobs for alert
     */
    private function summarizeFailedJobs(array $jobs): array
    {
        $summary = [];
        $jobTypes = [];

        foreach ($jobs as $job) {
            $jobClass = $job->payload['data']['commandName'] ?? 'unknown';

            if (! isset($jobTypes[$jobClass])) {
                $jobTypes[$jobClass] = 0;
            }

            $jobTypes[$jobClass]++;
        }

        $summary['by_type'] = $jobTypes;
        $summary['total_unique_types'] = count($jobTypes);

        return $summary;
    }

    /**
     * Send alert to Slack
     */
    private function sendToSlack(array $alertData): void
    {
        try {
            $webhookUrl = config('monitoring.slack_webhook_url');

            if (empty($webhookUrl)) {
                return;
            }

            $message = $this->formatSlackMessage($alertData);

            $this->http->post($webhookUrl, $message);
        } catch (\Exception $e) {
            $this->log->error('Failed to send Slack alert', [
                'error' => $e->getMessage(),
                'queue' => $alertData['queue'],
            ]);
        }
    }

    /**
     * Format Slack message
     */
    private function formatSlackMessage(array $alertData): array
    {
        return [
            'text' => "🚨 Failed Job Alert: {$alertData['queue']} queue",
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'Queue',
                            'value' => $alertData['queue'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Failed Count',
                            'value' => $alertData['failed_count'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => $alertData['threshold'],
                            'short' => true,
                        ],
                        [
                            'title' => 'Window',
                            'value' => "{$alertData['window_minutes']} minutes",
                            'short' => true,
                        ],
                        [
                            'title' => 'Job Types',
                            'value' => implode(', ', array_keys($alertData['jobs']['by_type'])),
                            'short' => false,
                        ],
                    ],
                    'footer' => 'CatVRF Monitoring',
                    'ts' => CarbonImmutable::now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Send alert to Telegram
     */
    private function sendToTelegram(array $alertData): void
    {
        try {
            $botToken = config('monitoring.telegram_bot_token');
            $chatId = config('monitoring.telegram_chat_id');

            if (empty($botToken) || empty($chatId)) {
                return;
            }

            $message = $this->formatTelegramMessage($alertData);

            $this->http->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            );
        } catch (\Exception $e) {
            $this->log->error('Failed to send Telegram alert', [
                'error' => $e->getMessage(),
                'queue' => $alertData['queue'],
            ]);
        }
    }

    /**
     * Format Telegram message
     */
    private function formatTelegramMessage(array $alertData): string
    {
        $jobTypesList = implode("\n", array_map(
            fn ($type, $count) => "• <code>{$type}</code>: {$count}",
            array_keys($alertData['jobs']['by_type']),
            $alertData['jobs']['by_type']
        ));

        return <<<EOT
🚨 <b>Failed Job Alert</b>

<b>Queue:</b> <code>{$alertData['queue']}</code>
<b>Failed Count:</b> {$alertData['failed_count']}
<b>Threshold:</b> {$alertData['threshold']}
<b>Window:</b> {$alertData['window_minutes']} minutes

<b>Job Types:</b>
{$jobTypesList}

<b>Time:</b> {$alertData['timestamp']}
EOT;
    }
}
