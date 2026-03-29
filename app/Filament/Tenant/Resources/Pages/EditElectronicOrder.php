<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ElectronicOrder\Pages;

use use App\Filament\Tenant\Resources\ElectronicOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditElectronicOrder extends EditRecord
{
    protected static string $resource = ElectronicOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit ElectronicOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}