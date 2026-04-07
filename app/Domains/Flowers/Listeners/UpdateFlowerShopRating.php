<?php declare(strict_types=1);

namespace App\Domains\Flowers\Listeners;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
final class UpdateFlowerShopRating
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function handle(FlowerDeliveryCompleted $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $order = $event->delivery->order;

                    $this->logger->info('Update flower shop rating', [
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

                            $this->logger->info('Shop rating updated', [
                                'shop_id' => $shop->id,
                                'new_rating' => $shop->rating,
                                'review_count' => $shop->review_count,
                                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                            ]);
                        }
                    }
                });
            } catch (\Throwable $exception) {
                $this->logger->error('Rating update failed', [
                    'shop_id' => $event->delivery->shop_id,
                    'error' => $exception->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $exception;
            }
        }
}
