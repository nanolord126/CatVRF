<?php declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;

use App\Domains\Flowers\Events\FlowerDeliveryCompleted;
use App\Domains\Flowers\Models\FlowerShop;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateFlowerShopRating implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(FlowerDeliveryCompleted $event): void
    {
        try {
            $this->db->transaction(function () use ($event) {
                $order = $event->delivery->order;
                
                $this->log->channel('audit')->info('Update flower shop rating', [
                    'shop_id' => $event->delivery->shop_id,
                    'order_id' => $order->id,
                    'correlation_id' => $event->correlationId,
                ]);

                $shop = FlowerShop::query()
                    ->where('id', $event->delivery->shop_id)
                    ->where('tenant_id', $event->delivery->tenant_id)
                    ->lockForUpdate()
                    ->first();

                if ($shop) {
                    $reviews = $shop->reviews()
                        ->where('status', 'approved')
                        ->get();

                    if ($reviews->isNotEmpty()) {
                        $averageRating = $reviews->avg('overall_rating');
                        $shop->update([
                            'rating' => round($averageRating, 1),
                            'review_count' => $reviews->count(),
                        ]);

                        $this->log->channel('audit')->info('Shop rating updated', [
                            'shop_id' => $shop->id,
                            'new_rating' => $shop->rating,
                            'review_count' => $shop->review_count,
                        ]);
                    }
                }
            });
        } catch (\Exception $exception) {
            $this->log->channel('audit')->error('Rating update failed', [
                'shop_id' => $event->delivery->shop_id,
                'error' => $exception->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $exception;
        }
    }
}
