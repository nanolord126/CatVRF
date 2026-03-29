<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportingGoods\Pages;

use use App\Filament\Tenant\Resources\SportingGoodsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditSportingGoods extends EditRecord
{
    protected static string $resource = SportingGoodsResource::class;

    public function getTitle(): string
    {
        return 'Edit SportingGoods';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}