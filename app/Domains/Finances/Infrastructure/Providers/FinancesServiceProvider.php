<?php

declare(strict_types=1);

namespace App\Domains\Finances\Infrastructure\Providers;

use App\Domains\Finances\Domain\Interfaces\EarningCalculatorInterface;
use App\Domains\Finances\Domain\Interfaces\PayoutRepositoryInterface;
use App\Domains\Finances\Domain\Interfaces\TransactionRepositoryInterface;
use App\Domains\Finances\Domain\Services\PayoutService;
use App\Domains\Finances\Infrastructure\Persistence\EloquentTransactionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Провайдер домена Finances.
 *
 * Регистрирует:
 * - Байндинги интерфейсов на реализации
 * - Тегирование калькуляторов по вертикалям
 * - PayoutService с инжекцией всех тегированных калькуляторов
 *
 * @package App\Domains\Finances\Infrastructure\Providers
 */
final class FinancesServiceProvider extends ServiceProvider
{
    /**
     * Регистрация байндингов в контейнере.
     */
    public function register(): void
    {
        $this->app->bind(
            TransactionRepositoryInterface::class,
            EloquentTransactionRepository::class,
        );

        $this->app->bind(
            PayoutRepositoryInterface::class,
            \App\Domains\Finances\Infrastructure\Persistence\EloquentPayoutRepository::class,
        );

        $this->registerEarningCalculators();
        $this->registerPayoutService();
    }

    /**
     * Бутстрап: регистрация событий, observers, миграций.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes([
            __DIR__ . '/../../config/finances.php' => config_path('finances.php'),
        ], 'finances-config');
    }

    /**
     * Тегирование калькуляторов заработка по вертикалям.
     *
     * Каждая вертикаль регистрирует свой калькулятор,
     * реализующий EarningCalculatorInterface.
     */
    private function registerEarningCalculators(): void
    {
        $calculators = config('finances.earning_calculators', []);

        if (empty($calculators)) {
            $calculators = $this->discoverEarningCalculators();
        }

        if (!empty($calculators)) {
            $this->app->tag($calculators, EarningCalculatorInterface::class);
        }
    }

    /**
     * Регистрация PayoutService с инжекцией тегированных калькуляторов.
     */
    private function registerPayoutService(): void
    {
        $this->app->singleton(PayoutService::class, function ($app) {
            return new PayoutService(
                payoutRepository: $app->make(PayoutRepositoryInterface::class),
                walletService: $app->make(\App\Services\WalletService::class),
                fraud: $app->make(\App\Services\FraudControlService::class),
                audit: $app->make(\App\Services\AuditService::class),
                calculators: $app->tagged(EarningCalculatorInterface::class),
                db: $app->make(\Illuminate\Database\DatabaseManager::class),
                events: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class),
            );
        });
    }

    /**
     * Автообнаружение классов калькуляторов по конвенции имён.
     *
     * @return array<class-string<EarningCalculatorInterface>>
     */
    private function discoverEarningCalculators(): array
    {
        $calculators = [];
        $domainsPath = app_path('Domains');

        if (!is_dir($domainsPath)) {
            return $calculators;
        }

        foreach (scandir($domainsPath) ?: [] as $domain) {
            if (in_array($domain, ['.', '..', 'Finances'], true)) {
                continue;
            }

            $className = "App\\Domains\\{$domain}\\Services\\{$domain}EarningCalculator";

            if (class_exists($className) && is_subclass_of($className, EarningCalculatorInterface::class)) {
                $calculators[] = $className;
            }
        }

        return $calculators;
    }
}
