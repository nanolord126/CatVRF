<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Electronics\Pages;

use use App\Filament\Tenant\Resources\ElectronicsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditElectronics extends EditRecord
{
    protected static string $resource = ElectronicsResource::class;

    public function getTitle(): string
    {
        return 'Edit Electronics';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}