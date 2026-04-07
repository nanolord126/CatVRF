<?php declare(strict_types=1);

namespace App\Domains\Flowers\Jobs;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class CalculateFlowerShopEarningsJob
{
    public function __construct(private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use Queueable;
use App\Services\FraudControlService;

        public $tries = 3;

        public function handle(): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
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
}
