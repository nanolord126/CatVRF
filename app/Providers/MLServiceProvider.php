<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use App\Services\ML\TasteMLService;
use App\Services\ML\UserTasteProfileService;
use App\Services\AI\AIBeautyConstructorService;
use OpenAI\Client;

final class MLServiceProvider extends ServiceProvider
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

    

    /**
         * Register ML/AI services
         */
        public function register(): void
        {
            // Singleton: TasteMLService
            $this->app->singleton(TasteMLService::class, function () {
                return new TasteMLService(
                    request: app(Request::class),
                    openai: app(Client::class),
                    logger: $this->logger->channel('audit'),
                    db: app(DatabaseManager::class),
                );
            });

            // Singleton: UserTasteProfileService
            $this->app->singleton(UserTasteProfileService::class, function () {
                return new UserTasteProfileService(
                    mlService: app(TasteMLService::class),
                    logger: $this->logger->channel('audit'),
                    db: app(DatabaseManager::class),
                );
            });

            // Singleton: AIBeautyConstructorService
            $this->app->singleton(AIBeautyConstructorService::class, function () {
                return new AIBeautyConstructorService(
                    tasteService: app(UserTasteProfileService::class),
                    openai: app(Client::class),
                    logger: $this->logger->channel('audit'),
                );
            });
        }

        /**
         * Bootstrap ML/AI services
         */
        public function boot(): void
        {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/taste-ml.php' => config_path('taste-ml.php'),
            ], 'taste-ml-config');

            // Register migrations
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'taste-ml-migrations');
        }
}
