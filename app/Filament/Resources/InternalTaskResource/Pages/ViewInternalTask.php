<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalTaskResource\Pages;

use App\Filament\Resources\InternalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewInternalTask extends ViewRecord
{
    protected static string $resource = InternalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
