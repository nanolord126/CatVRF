<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ServiceResource\Pages;

use App\Filament\Tenant\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
