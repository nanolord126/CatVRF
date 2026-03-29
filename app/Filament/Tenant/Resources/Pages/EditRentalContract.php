<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RentalContract\Pages;

use use App\Filament\Tenant\Resources\RentalContractResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditRentalContract extends EditRecord
{
    protected static string $resource = RentalContractResource::class;

    public function getTitle(): string
    {
        return 'Edit RentalContract';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}