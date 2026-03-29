<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry\Pages;

use use App\Filament\Tenant\Resources\JewelryResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditJewelry extends EditRecord
{
    protected static string $resource = JewelryResource::class;

    public function getTitle(): string
    {
        return 'Edit Jewelry';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}