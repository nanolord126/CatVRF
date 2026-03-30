<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MLServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Register ML/AI services
         */
        public function register(): void
        {
            // Singleton: TasteMLService
            $this->app->singleton(TasteMLService::class, function () {
                return new TasteMLService(
                    client: app(\OpenAI\Client::class),
                    redisConnection: \Illuminate\Support\Facades\Redis::connection(),
                    logger: \Illuminate\Support\Facades\Log::channel('audit'),
                );
            });

            // Singleton: UserTasteProfileService
            $this->app->singleton(UserTasteProfileService::class, function () {
                return new UserTasteProfileService(
                    mlService: app(TasteMLService::class),
                    logger: \Illuminate\Support\Facades\Log::channel('audit'),
                );
            });

            // Singleton: AIBeautyConstructorService
            $this->app->singleton(AIBeautyConstructorService::class, function () {
                return new AIBeautyConstructorService(
                    client: app(\OpenAI\Client::class),
                    tasteProfileService: app(UserTasteProfileService::class),
                    logger: \Illuminate\Support\Facades\Log::channel('audit'),
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
