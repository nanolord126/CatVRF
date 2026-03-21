<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShopResource\Pages;

use App\Filament\Tenant\Resources\MeatShopResource;
use Filament\Resources\Pages\EditRecord;

final class EditMeatShop extends EditRecord
{
    protected static string $resource = MeatShopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
