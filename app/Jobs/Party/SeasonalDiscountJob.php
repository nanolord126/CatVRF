<?php declare(strict_types=1);

namespace App\Jobs\Party;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SeasonalDiscountJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private string $correlationId
        ) {}

        /**
         * Execute the job.
         */
        public function handle(): void
        {
            Log::channel('audit')->info('Starting SeasonalDiscountJob', [
                'correlation_id' => $this->correlationId,
            ]);

            DB::transaction(function () {
                $now = now();

                // Get themes ending within 24 hours (for clearance sales)
                $endingThemes = PartyTheme::where('is_active', true)
                    ->where('is_seasonal', true)
                    ->whereDate('season_end', $now->toDateString())
                    ->get();

                foreach ($endingThemes as $theme) {
                    $this->applyClearanceDiscount($theme);
                }

                // Get themes starting today
                $startingThemes = PartyTheme::where('is_active', true)
                    ->where('is_seasonal', true)
                    ->whereDate('season_start', $now->toDateString())
                    ->get();

                foreach ($startingThemes as $theme) {
                    Log::channel('audit')->info("New season theme active: {$theme->name}", [
                        'theme_id' => $theme->id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            });

            Log::channel('audit')->info('SeasonalDiscountJob completed', [
                'correlation_id' => $this->correlationId,
            ]);
        }

        /**
         * Apply 20% discount for products in ending seasonal themes.
         */
        private function applyClearanceDiscount(PartyTheme $theme): void
        {
            $products = PartyProduct::where('party_theme_id', $theme->id)
                ->where('is_active', true)
                ->get();

            foreach ($products as $product) {
                $originalPrice = $product->price_cents;
                $discountedPrice = (int) ($originalPrice * 0.8); // 20% OFF

                $product->update([
                    'price_cents' => $discountedPrice,
                    'metadata' => array_merge($product->metadata ?? [], [
                        'clearance_sale' => true,
                        'original_price' => $originalPrice,
                        'applied_at' => now()->toIso8601String(),
                    ])
                ]);

                Log::channel('audit')->info("Clearance discount applied to festive item", [
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
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private string $correlationId
        ) {}

        public function handle(): void
        {
            Log::channel('audit')->info('Starting EventReminderJob', [
                'correlation_id' => $this->correlationId,
            ]);

            $upcomingOrders = \App\Models\Party\PartyOrder::where('status', 'confirmed')
                ->whereDate('event_date', now()->addDays(2)->toDateString())
                ->get();

            foreach ($upcomingOrders as $order) {
                $this->notifyUser($order);
            }

            Log::channel('audit')->info('EventReminderJob finished', [
                'correlation_id' => $this->correlationId,
                'reminders_sent' => $upcomingOrders->count(),
            ]);
        }

        private function notifyUser(\App\Models\Party\PartyOrder $order): void
        {
            // (Simulation of notification delivery via Mail/SMS/Push)
            Log::channel('audit')->info("Celebration reminder sent to user", [
                'order_uuid' => $order->uuid,
                'user_id' => $order->user_id,
                'event_date' => $order->event_date,
                'correlation_id' => $this->correlationId,
            ]);
        }
}
