<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Supermarket\Models\Subscription;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:reminders {--hours=24 : Send reminders for subscriptions due within X hours}';
    protected $description = 'Send pre-delivery reminders for subscriptions';

    public function handle(SubscriptionNotificationService $notificationService): int
    {
        $hours = (int) $this->option('hours');
        
        $this->info("Sending reminders for subscriptions due within {$hours} hours");

        $subscriptions = Subscription::where('status', 'active')
            ->whereBetween('next_delivery_at', [
                now()->addHours($hours - 2),
                now()->addHours($hours + 2)
            ])
            ->with(['buyer'])
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions to remind");

        if ($subscriptions->isEmpty()) {
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->info("Sending reminder for subscription #{$subscription->id} to buyer #{$subscription->buyer_id}");
                
                $notificationService->sendPreDeliveryReminder($subscription);
                
                $sent++;
                $this->info("✓ Reminder sent for subscription #{$subscription->id}");
                
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Failed to send reminder for subscription #{$subscription->id}: {$e->getMessage()}");
                Log::error('Failed to send subscription reminder', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        $this->info("Reminder sending complete:");
        $this->line("  Sent: {$sent}");
        $this->line("  Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
