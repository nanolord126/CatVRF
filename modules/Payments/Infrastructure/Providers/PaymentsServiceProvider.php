<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Payments\Application\Ports\EventDispatcherPort;
use Modules\Payments\Application\Ports\LoggerPort;
use Modules\Payments\Application\Ports\PaymentGatewayPort;
use Modules\Payments\Application\Ports\PaymentRepositoryPort;
use Modules\Payments\Application\Ports\TransactionManagerPort;
use Modules\Payments\Application\Ports\WalletAdapterPort;
use Modules\Payments\Infrastructure\Adapters\Gateway\TinkoffBusinessGateway;
use Modules\Payments\Infrastructure\Adapters\Storage\EloquentPaymentRepository;
use Modules\Payments\Infrastructure\Adapters\System\LaravelEventDispatcher;
use Modules\Payments\Infrastructure\Adapters\System\LaravelLogger;
use Modules\Payments\Infrastructure\Adapters\System\LaravelTransactionManager;

/**
 * Class PaymentsServiceProvider
 * 
 * Binds explicit safely infrastructural bounds dynamic structurally mappings securely logically natively execution metrics properly resolving accurately logic correctly physically resolving reliable strict boundaries constraints inherently.
 */
class PaymentsServiceProvider extends ServiceProvider
{
    /**
     * Integrates resolving safe structural constraints strictly.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(LoggerPort::class, LaravelLogger::class);
        $this->app->singleton(EventDispatcherPort::class, LaravelEventDispatcher::class);
        $this->app->singleton(TransactionManagerPort::class, LaravelTransactionManager::class);
        $this->app->singleton(PaymentRepositoryPort::class, EloquentPaymentRepository::class);

        $this->app->singleton(PaymentGatewayPort::class, function ($app) {
            return new TinkoffBusinessGateway(
                httpClient: $app->make(\Illuminate\Http\Client\Factory::class),
                logger: $app->make(LoggerPort::class),
                terminalKey: config('services.tinkoff.terminal_key', ''),
                secretKey: config('services.tinkoff.secret_key', '')
            );
        });

        // Wallet adapter will be bound once Wallet Module is natively implemented
        // $this->app->singleton(WalletAdapterPort::class, WalletModuleAdapter::class);
    }

    /**
     * Boots structural routes mappings effectively dynamically securely resolving natively.
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Persistence/Migrations');
    }
}
