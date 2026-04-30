<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource\Pages;

use App\Filament\Tenant\Resources\Logistics\CourierTypeConfigurationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCourierTypeConfigurations extends ListRecords
{
    protected static string $resource = CourierTypeConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
