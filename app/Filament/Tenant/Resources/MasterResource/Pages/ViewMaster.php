<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MasterResource\Pages;

use App\Filament\Tenant\Resources\MasterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewMaster extends ViewRecord
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
