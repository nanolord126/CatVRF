<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MasterResource\Pages;

use App\Filament\Tenant\Resources\MasterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
