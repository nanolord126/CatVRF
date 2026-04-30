<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Supermarket\Models\Subscription;
use App\Domains\Supermarket\Services\SubscriptionService;
use App\Domains\Supermarket\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final class ProcessSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:process {--dry-run : Run without actually processing}';
    protected $description = 'Process due subscriptions and create orders';

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly SubscriptionNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $startTime = now();

        $this->info('Starting subscription processing...');
        $this->info("Dry run mode: " . ($dryRun ? 'YES' : 'NO'));

        try {
            $dueSubscriptions = $this->getDueSubscriptions();
            $count = $dueSubscriptions->count();

            $this->info("Found {$count} due subscriptions");

            if ($count === 0) {
                $this->info('No subscriptions to process.');
                return self::SUCCESS;
            }

            $this->newLine();
            $this->output->progressStart($count);

            $processed = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($dueSubscriptions as $subscription) {
                $this->output->progressAdvance();

                try {
                    if ($dryRun) {
                        $this->line("  Would process subscription #{$subscription->id}");
                        $processed++;
                        continue;
                    }

                    $result = $this->processSubscription($subscription);

                    if ($result === 'processed') {
                        $processed++;
                    } elseif ($result === 'skipped') {
                        $skipped++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("  Failed to process subscription #{$subscription->id}: {$e->getMessage()}");
                    Log::error('Failed to process individual subscription', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->output->progressFinish();

            $duration = $startTime->diffInSeconds(now());

            $this->newLine();
            $this->info('Subscription processing completed.');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total due', $count],
                    ['Processed', $processed],
                    ['Skipped', $skipped],
                    ['Failed', $failed],
                    ['Duration', "{$duration}s"],
                ]
            );

            Log::info('Subscriptions processed via command', [
                'total' => $count,
                'processed' => $processed,
                'failed' => $failed,
                'skipped' => $skipped,
                'duration_seconds' => $duration,
                'dry_run' => $dryRun,
            ]);

            return $failed > 0 ? self::FAILURE : self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to process subscriptions: {$e->getMessage()}");
            Log::error('Failed to process subscriptions via command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function getDueSubscriptions()
    {
        return Subscription::where('status', 'active')
            ->where('next_delivery_at', '<=', now())
            ->with(['items.product', 'buyer'])
            ->orderBy('next_delivery_at')
            ->get();
    }

    private function processSubscription(Subscription $subscription): string
    {
        // Check if buyer has any issues
        if ($this->hasBuyerIssues($subscription->buyer)) {
            $this->line("  Skipping subscription #{$subscription->id} - buyer has issues");
            return 'skipped';
        }

        // Check if seller is active
        if (!$this->isSellerActive($subscription->seller_id)) {
            $this->line("  Skipping subscription #{$subscription->id} - seller not active");
            $subscription->update(['status' => 'paused']);
            $this->notificationService->sendSubscriptionPaused($subscription, 'Seller not active');
            return 'skipped';
        }

        // Process the subscription
        DB::beginTransaction();
        try {
            $order = $this->subscriptionService->createNextOrder($subscription);

            DB::commit();

            $this->line("  Processed subscription #{$subscription->id} -> order #{$order->id}");
            $this->notificationService->sendDeliveryStarted($order);

            return 'processed';
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function hasBuyerIssues($buyer): bool
    {
        // Check if buyer has fraud flags or payment issues
        if (!$buyer) {
            return true;
        }

        // Check for failed payments
        $failedPayments = DB::table('subscription_payments')
            ->where('buyer_id', $buyer->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($failedPayments >= 3) {
            return true;
        }

        return false;
    }

    private function isSellerActive(?int $sellerId): bool
    {
        if (!$sellerId) {
            return true; // No seller restriction
        }

        return DB::table('tenants')
            ->where('id', $sellerId)
            ->where('status', 'active')
            ->exists();
    }
}
