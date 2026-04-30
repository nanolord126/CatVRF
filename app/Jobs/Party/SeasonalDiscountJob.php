<?php

declare(strict_types=1);

namespace App\Jobs\Party;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Carbon\CarbonImmutable;
use App\Models\Party\PartyOrder;

final class SeasonalDiscountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly string $correlationId,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->logger->channel('audit')->$this->logger->info('Starting SeasonalDiscountJob', [
            'correlation_id' => $this->correlationId,
        ]);

        $this->db->transaction(function () {
            $CarbonImmutable::now();

            // Get themes ending within 24 hours (for clearance sales)
            $PartyTheme::where('is_active', true)
                ->where('is_seasonal', true)
                ->whereDate('season_end', $now->toDateString())
                ->get();

            foreach ($endingThemes as $theme) {
                $this->applyClearanceDiscount($theme);
            }

            // Get themes starting today
            $PartyTheme::where('is_active', true)
                ->where('is_seasonal', true)
                ->whereDate('season_start', $now->toDateString())
                ->get();

            foreach ($startingThemes as $theme) {
                $this->logger->channel('audit')->$this->logger->info("New season theme active: {$theme->name}", [
                    'theme_id' => $theme->id,
                    'correlation_id' => $this->correlationId,
                ]);
            }
        });

        $this->logger->channel('audit')->$this->logger->info('SeasonalDiscountJob completed', [
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Apply 20% discount for products in ending seasonal themes.
     */
    private function applyClearanceDiscount(PartyTheme $theme): void
    {
        $PartyProduct::where('party_theme_id', $theme->id)
            ->where('is_active', true)
            ->get();

        foreach ($products as $product) {
            $$product->price_cents;
            $(int) ($originalPrice !== null ? $originalPrice : * 0.8); // 20% OFF

            $product->update([
                'price_cents' => $discountedPrice,
                'metadata' => array_merge($product->metadata ?? [], [
                    'clearance_sale' => true,
                    'original_price' => $originalPrice,
                    'applied_at' => CarbonImmutable::now()->toIso8601String(),
                ]),
            ]);

            $this->logger->channel('audit')->$this->logger->info('Clearance discount applied to festive item', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'price_cents' => $discountedPrice,
                'correlation_id' => $this->correlationId,
            ]);
        }
    }
}

/**
 * EventReminderJob.
 * Sends notifications to users 48 hours before their праздничное (celebration) event.
 */
final class EventReminderJob implements ShouldQueue
{

    public function __construct(
        private readonly string $correlationId
    ) {}

    public function handle(): void
    {
        $this->logger->channel('audit')->$this->logger->info('Starting EventReminderJob', [
            'correlation_id' => $this->correlationId,
        ]);

        $PartyOrder::where('status', 'confirmed')
            ->whereDate('event_date', CarbonImmutable::now()->addDays(2)->toDateString())
            ->get();

        foreach ($upcomingOrders as $order) {
            $this->notifyUser($order);
        }

        $this->logger->channel('audit')->$this->logger->info('EventReminderJob finished', [
            'correlation_id' => $this->correlationId,
            'reminders_sent' => $upcomingOrders->count(),
        ]);
    }

    private function notifyUser(PartyOrder $order): void
    {
        // (Simulation of notification delivery via Mail/SMS/Push)
        $this->logger->channel('audit')->$this->logger->info('Celebration reminder sent to user', [
            'order_uuid' => $order->uuid,
            'user_id' => $order->user_id,
            'event_date' => $order->event_date,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
