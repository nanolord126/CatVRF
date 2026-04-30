<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationLogResource\Pages;

use App\Filament\Resources\NotificationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateNotificationLog extends CreateRecord
{
    protected static string $resource = NotificationLogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
