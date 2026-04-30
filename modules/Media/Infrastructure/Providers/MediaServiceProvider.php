<?php

declare(strict_types=1);

namespace Modules\Media\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Media\Application\Services\BulkImportService;
use Modules\Media\Application\Services\MediaUploadService;
use Modules\Media\Application\Services\MediaValidationService;
use Modules\Media\Domain\Repositories\MediaRepositoryInterface;
use Modules\Media\Infrastructure\Repositories\EloquentMediaRepository;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);
        $this->app->singleton(MediaUploadService::class);
        $this->app->singleton(MediaValidationService::class);
        $this->app->singleton(BulkImportService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
