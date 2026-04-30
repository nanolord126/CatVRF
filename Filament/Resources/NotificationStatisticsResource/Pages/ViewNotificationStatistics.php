<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationStatisticsResource\Pages;

use App\Filament\Resources\NotificationStatisticsResource;
use Filament\Pages\Page;
use Filament\Pages\Actions\Action;

final class ViewNotificationStatistics extends Page
{
    protected static string $resource = NotificationStatisticsResource::class;

    protected static string $view = 'filament.resources.notification-statistics.pages.view';

    protected function getHeaderWidgets(): array
    {
        return NotificationStatisticsResource::getWidgets();
    }
}
