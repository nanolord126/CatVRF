<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Wallet\Application\Ports\WalletRepositoryPort;
use Modules\Wallet\Application\Ports\FraudCheckPort;
use Modules\Wallet\Infrastructure\Adapters\Storage\EloquentWalletRepository;
use Modules\Wallet\Infrastructure\Adapters\System\WalletFraudCheckAdapter;

// Note: Re-using the common System adapters from generic domain if they exist, 
// or wiring them specifically to Laravel core tools as instructed by Hexagonal constraints.
use Modules\Wallet\Application\Ports\LoggerPort;
use Modules\Wallet\Application\Ports\EventDispatcherPort;
use Modules\Wallet\Application\Ports\TransactionManagerPort;
use Modules\Payments\Infrastructure\Adapters\System\LaravelLoggerAdapter; // Re-using adapter
use Modules\Payments\Infrastructure\Adapters\System\LaravelEventDispatcherAdapter; // Re-using adapter
use Modules\Payments\Infrastructure\Adapters\System\LaravelTransactionManagerAdapter; // Re-using adapter

/**
 * Class WalletServiceProvider
 *
 * Registers absolute DI bindings isolating the exact Ports & Adapters mechanics.
 * Maps interfaces defined in Application layer to concrete infrastructural tools natively.
 * Enforces correct lifecycle scoping ensuring objects map flawlessly.
 */
final class WalletServiceProvider extends ServiceProvider
{
    /**
     * Define internal dependency bindings mapping safely interface definitions
     * mapped reliably onto infrastructural solutions cleanly resolving inversion.
     * All components must resolve without explicit tight coupling in logic.
     *
     * @return void
     */
    public function register(): void
    {
        // 1. Storage Repository Binding
        // Ensures UseCases demanding standard Wallet stores retrieve the concrete database implementation
        $this->app->scoped(WalletRepositoryPort::class, EloquentWalletRepository::class);

        // 2. Fraud Service Binding
        // Directs pure security assertions down into the platform ML engine via Adapter pattern
        $this->app->scoped(FraudCheckPort::class, WalletFraudCheckAdapter::class);

        // 3. System Adapters (Re-using generic structural equivalents usually mapped per domain)
        // If these equivalents were strictly namespaced to Payments, we would recreate them in Wallet.
        // Assuming shared infrastructure generic components can be bound correctly.
        if (class_exists(LaravelLoggerAdapter::class)) {
            $this->app->bind(LoggerPort::class, LaravelLoggerAdapter::class);
            $this->app->bind(EventDispatcherPort::class, LaravelEventDispatcherAdapter::class);
            $this->app->bind(TransactionManagerPort::class, LaravelTransactionManagerAdapter::class);
        }
        
        // Expose robust registration mechanics cleanly
        $this->registerCommands();
    }

    /**
     * Bootstraps native module definitions enforcing HTTP routes reliably.
     * Structurally hooks strictly validated routes over standard MVC mappings safely.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
    }

    /**
     * Resolves absolute route definition files structurally tracking mapping cleanly.
     * Exposes isolated endpoint definitions securely matching external endpoints.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::middleware('api')
            ->group(base_path('modules/Wallet/Presentation/Routes/api.php'));
    }

    /**
     * Handles isolated table structural configurations strictly mapping localized state.
     * Ensures explicit separation handling mapping securely databases inherently.
     *
     * @return void
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(base_path('modules/Wallet/Infrastructure/Migrations'));
    }

    /**
     * Pushes localized structural CLI configurations matching execution logically cleanly
     * routing operations reliably safely constraints bounds structurally cleanly.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Console commands could be registered here
            ]);
        }
    }
}
