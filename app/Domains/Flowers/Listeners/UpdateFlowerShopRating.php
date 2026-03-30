<?php declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateFlowerShopRating extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(FlowerDeliveryCompleted $event): void
        {
            try {
                DB::transaction(function () use ($event) {
                    $order = $event->delivery->order;

                    Log::channel('audit')->info('Update flower shop rating', [
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

                            Log::channel('audit')->info('Shop rating updated', [
                                'shop_id' => $shop->id,
                                'new_rating' => $shop->rating,
                                'review_count' => $shop->review_count,
                            ]);
                        }
                    }
                });
            } catch (\Exception $exception) {
                Log::channel('audit')->error('Rating update failed', [
                    'shop_id' => $event->delivery->shop_id,
                    'error' => $exception->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $exception;
            }
        }
}
