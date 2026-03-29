<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\JewelryProduct\Pages;

use use App\Filament\Tenant\Resources\JewelryProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditJewelryProduct extends EditRecord
{
    protected static string $resource = JewelryProductResource::class;

    public function getTitle(): string
    {
        return 'Edit JewelryProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}