<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages;

use App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource;
use Filament\Resources\Pages\ListRecords;

final class ListNotificationExperiments extends ListRecords
{
    protected static string $resource = NotificationExperimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\CreateAction::make(),
        ];
    }
}
