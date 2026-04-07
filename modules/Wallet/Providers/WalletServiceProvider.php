<?php

declare(strict_types=1);

namespace Modules\Wallet\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Wallet\Adapters\FraudCheckAdapter;
use Modules\Wallet\Application\Queries\GetWalletBalanceQuery;
use Modules\Wallet\Application\UseCases\Deposit\DepositUseCase;
use Modules\Wallet\Application\UseCases\Transfer\TransferUseCase;
use Modules\Wallet\Application\UseCases\Withdraw\WithdrawUseCase;
use Modules\Wallet\Domain\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Infrastructure\Repositories\BavixWalletRepository;
use Modules\Wallet\Ports\FraudCheckPort;

/**
 * ServiceProvider: регистрация зависимостей модуля Wallet.
 */
final class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository
        $this->app->bind(
            WalletRepositoryInterface::class,
            BavixWalletRepository::class,
        );

        // Ports
        $this->app->bind(
            FraudCheckPort::class,
            FraudCheckAdapter::class,
        );

        // Application UseCases
        $this->app->bind(DepositUseCase::class, function ($app) {
            return new DepositUseCase(
                wallets: $app->make(WalletRepositoryInterface::class),
                fraud:   $app->make(FraudCheckPort::class),
            );
        });

        $this->app->bind(WithdrawUseCase::class, function ($app) {
            return new WithdrawUseCase(
                wallets: $app->make(WalletRepositoryInterface::class),
                fraud:   $app->make(FraudCheckPort::class),
            );
        });

        $this->app->bind(TransferUseCase::class, function ($app) {
            return new TransferUseCase(
                wallets: $app->make(WalletRepositoryInterface::class),
                fraud:   $app->make(FraudCheckPort::class),
            );
        });

        $this->app->bind(GetWalletBalanceQuery::class, function ($app) {
            return new GetWalletBalanceQuery(
                wallets: $app->make(WalletRepositoryInterface::class),
            );
        });
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
