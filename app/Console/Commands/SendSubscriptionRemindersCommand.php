<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Modules\Supermarket\Application\Services\SubscriptionNotificationService;
use Modules\Supermarket\Infrastructure\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SendSubscriptionRemindersCommand extends Command
{
    protected $signature = 'subscriptions:reminders {--hours=24 : Hours before delivery to send reminder}';
    protected $description = 'Send pre-delivery reminders for subscriptions';

    public function handle(SubscriptionNotificationService $notificationService): int
    {
        $hours = (int) $this->option('hours');
        $this->info("Sending reminders for subscriptions in {$hours} hours...");

        try {
            $subscriptions = Subscription::active()
                ->whereBetween('next_delivery_at', [
                    now()->addHours($hours - 1),
                    now()->addHours($hours + 1),
                ])
                ->with(['buyer'])
                ->get();

            $sentCount = 0;

            foreach ($subscriptions as $subscription) {
                try {
                    $notificationService->sendPreDeliveryReminder($subscription);
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to send subscription reminder', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Sent {$sentCount} reminders");

            Log::info('Subscription reminders sent', [
                'count' => $sentCount,
                'hours_before' => $hours,
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send reminders: {$e->getMessage()}");
            Log::error('Failed to send subscription reminders', [
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }
}
