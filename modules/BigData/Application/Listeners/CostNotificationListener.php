<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Events\BudgetExceeded;
use Modules\BigData\Domain\Events\CostAnomalyDetected;
use Modules\BigData\Domain\Events\OptimizationApplied;

/**
 * Cost Notification Listener
 *
 * Handles cost-related domain events and dispatches notifications
 * to Telegram, Slack, and PagerDuty based on configuration.
 */
final class CostNotificationListener
{
    public function handleBudgetExceeded(BudgetExceeded $event): void
    {
        $notification = $event->toNotification();

        Log::warning('Budget exceeded', $notification);

        $this->sendToTelegram($notification);
        $this->sendToSlack($notification);

        if ($event->budgetStatus === \Modules\BigData\Domain\Enums\BudgetStatus::CriticalOver) {
            $this->sendToPagerDuty($notification);
        }
    }

    public function handleCostAnomaly(CostAnomalyDetected $event): void
    {
        $notification = $event->toNotification();

        Log::warning('Cost anomaly detected', $notification);

        $this->sendToSlack($notification);

        if ($event->severity === \Modules\BigData\Domain\Enums\AlertSeverity::Critical) {
            $this->sendToPagerDuty($notification);
            $this->sendToTelegram($notification);
        }
    }

    public function handleOptimizationApplied(OptimizationApplied $event): void
    {
        $notification = $event->toNotification();

        Log::info('Optimization applied', $notification);

        // Only notify Slack for applied optimizations
        $this->sendToSlack($notification);
    }

    private function sendToTelegram(array $notification): void
    {
        if (!config('bigdata.cost.notify_telegram', false)) {
            return;
        }

        $chatId = config('bigdata.cost.telegram_chat_id');
        if (empty($chatId)) {
            return;
        }

        try {
            $message = "🔴 *{$notification['event']}*\n"
                . $notification['message'] . "\n"
                . "Severity: `{$notification['severity']}`";

            Http::post("https://api.telegram.org/bot" . config('services.telegram.bot_token') . "/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::error('Cost notification: Telegram failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendToSlack(array $notification): void
    {
        if (!config('bigdata.cost.notify_slack', false)) {
            return;
        }

        $webhook = config('bigdata.cost.slack_webhook_url');
        if (empty($webhook)) {
            return;
        }

        try {
            $color = match ($notification['severity'] ?? 'info') {
                'critical' => 'danger',
                'warning' => 'warning',
                default => 'good',
            };

            Http::post($webhook, [
                'attachments' => [[
                    'color' => $color,
                    'title' => "FinOps: {$notification['event']}",
                    'text' => $notification['message'],
                    'fields' => collect($notification)
                        ->except(['event', 'message'])
                        ->map(fn($v, $k) => ['title' => $k, 'value' => (string) $v, 'short' => true])
                        ->values()
                        ->toArray(),
                    'ts' => time(),
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::error('Cost notification: Slack failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendToPagerDuty(array $notification): void
    {
        if (!config('bigdata.cost.notify_pagerduty', false)) {
            return;
        }

        $routingKey = config('bigdata.cost.pagerduty_routing_key');
        if (empty($routingKey)) {
            return;
        }

        try {
            Http::withHeaders(['Content-Type' => 'application/json'])
                ->post('https://events.pagerduty.com/v2/enqueue', [
                    'routing_key' => $routingKey,
                    'event_action' => 'trigger',
                    'payload' => [
                        'summary' => $notification['message'],
                        'severity' => $notification['severity'] ?? 'warning',
                        'source' => 'catvrf-bigdata-finops',
                        'component' => 'bigdata-cost-monitoring',
                        'group' => 'finops',
                        'class' => 'cost',
                        'custom_details' => $notification,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('Cost notification: PagerDuty failed', ['error' => $e->getMessage()]);
        }
    }
}
