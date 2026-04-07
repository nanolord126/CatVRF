<?php declare(strict_types=1);

namespace Modules\Payments\Providers;

use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use Modules\Payments\Gateways\TinkoffGateway;
use Modules\Payments\Services\IdempotencyService;
use Modules\Payments\Services\PaymentOrchestrator;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $config = config('payments.tinkoff');

            return new TinkoffGateway(
                http: $app->make(HttpFactory::class),
                config: $config,
            );
        });

        $this->app->singleton(IdempotencyService::class, fn ($app) => new IdempotencyService(
            db: $app->make('db'),
            log: $app->make('log'),
        ));

        $this->app->singleton(PaymentOrchestrator::class, fn ($app) => new PaymentOrchestrator(
            db: $app->make('db'),
            log: $app->make('log'),
            fraud: $app->make(FraudControlService::class),
            wallet: $app->make(WalletService::class),
            idempotency: $app->make(IdempotencyService::class),
            gateway: $app->make(PaymentGatewayInterface::class),
        ));
    }

    public function boot(): void
    {
        if (is_dir(__DIR__ . '/../Routes') && file_exists(__DIR__ . '/../Routes/api.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        }
    }
}
