<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirect\Pages;

use use App\Filament\Tenant\Resources\FarmDirectResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFarmDirect extends EditRecord
{
    protected static string $resource = FarmDirectResource::class;

    public function getTitle(): string
    {
        return 'Edit FarmDirect';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}