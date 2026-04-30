<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Jobs;

use App\Domains\Flowers\Models\FlowerShop;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;

final class CalculateFlowerShopEarningsJob implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly Request $request,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
        private readonly FraudControlService $fraud,
    ) {}

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
                $shops = FlowerShop::query()
                    ->where('is_active', true)
                    ->get();

                foreach ($shops as $shop) {
                    $completedOrders = $shop->orders()
                        ->where('status', 'delivered')
                        ->where('payment_status', 'paid')
                        ->where('created_at', '>=', Carbon::now()->subDay())
                        ->get();

                    $totalEarnings = $completedOrders->sum('total_amount');
                    $totalCommission = $completedOrders->sum('commission_amount');
                    $earnings = $totalEarnings - $totalCommission;

                    $this->logger->info('Flower shop earnings calculated', [
                        'shop_id' => $shop->id,
                        'orders_count' => $completedOrders->count(),
                        'earnings' => $earnings,
                        'commission' => $totalCommission,
                        'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            $this->logger->error('Flower shop earnings calculation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->error('flowers job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
