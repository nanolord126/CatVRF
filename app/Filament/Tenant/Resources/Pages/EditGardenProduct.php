<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\GardenProduct\Pages;

use use App\Filament\Tenant\Resources\GardenProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditGardenProduct extends EditRecord
{
    protected static string $resource = GardenProductResource::class;

    public function getTitle(): string
    {
        return 'Edit GardenProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}