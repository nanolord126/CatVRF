<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\JewelryCustomOrder\Pages;

use use App\Filament\Tenant\Resources\JewelryCustomOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditJewelryCustomOrder extends EditRecord
{
    protected static string $resource = JewelryCustomOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit JewelryCustomOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}