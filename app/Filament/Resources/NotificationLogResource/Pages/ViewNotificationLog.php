<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationLogResource\Pages;

use App\Filament\Resources\NotificationLogResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewNotificationLog extends ViewRecord
{
    protected static string $resource = NotificationLogResource::class;
}
