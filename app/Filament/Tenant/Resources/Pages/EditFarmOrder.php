<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmOrder\Pages;

use use App\Filament\Tenant\Resources\FarmOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFarmOrder extends EditRecord
{
    protected static string $resource = FarmOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit FarmOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}