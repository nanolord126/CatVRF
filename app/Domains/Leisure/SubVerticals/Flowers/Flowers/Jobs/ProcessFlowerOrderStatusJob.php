<?php declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Jobs;

use Carbon\Carbon;
use App\Domains\Flowers\Models\FlowerOrder;
use App\Services\FraudControlService;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessFlowerOrderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly FraudControlService $fraud,
    ) {}

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function tags(): array
    {
        return ['flowers', 'job'];
    }

    public function handle(): void
    {
        try {
            $this->fraud->check(
                userId: $this->guard->id() ?? 0,
                operationType: 'mutation',
                amount: 0,
                correlationId: $this->request->header('X-Correlation-ID', ''),
            );
            $this->db->transaction(function () {
                $orders = FlowerOrder::query()
                    ->where('status', 'confirmed')
                    ->where('delivery_date', '>=', Carbon::now()->toDateString())
                    ->where('delivery_date', '<', Carbon::now()->addDay()->toDateString())
                    ->get();

                foreach ($orders as $order) {
                    if ($order->items()->count() > 0) {
                        $order->update(['status' => 'preparing']);

                        $this->logger->info('Flower order marked as preparing', [
                            'order_id' => $order->id,
                            'shop_id' => $order->shop_id,
                            'correlation_id' => $order->correlation_id,
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            $this->logger->error('Flower order status processing failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
            throw $exception;
        }
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('flowers job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}