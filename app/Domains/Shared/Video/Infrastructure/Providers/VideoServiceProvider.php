<?php

declare(strict_types=1);

namespace Modules\Video\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Video\Application\Services\LiveKitService;
use Modules\Video\Application\Services\VideoRoomService;
use Modules\Video\Domain\Repositories\VideoRoomRepositoryInterface;
use Modules\Video\Infrastructure\Repositories\EloquentVideoRoomRepository;

final class VideoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VideoRoomRepositoryInterface::class, EloquentVideoRoomRepository::class);
        $this->app->singleton(VideoRoomService::class);
        $this->app->singleton(LiveKitService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
