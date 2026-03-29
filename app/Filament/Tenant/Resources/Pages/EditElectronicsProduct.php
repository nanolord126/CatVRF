<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ElectronicsProduct\Pages;

use use App\Filament\Tenant\Resources\ElectronicsProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditElectronicsProduct extends EditRecord
{
    protected static string $resource = ElectronicsProductResource::class;

    public function getTitle(): string
    {
        return 'Edit ElectronicsProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}