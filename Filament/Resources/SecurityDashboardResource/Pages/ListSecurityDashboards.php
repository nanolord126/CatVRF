<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityDashboardResource\Pages;

use App\Filament\Resources\SecurityDashboardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListSecurityDashboards extends ListRecords
{
    protected static string $resource = SecurityDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
