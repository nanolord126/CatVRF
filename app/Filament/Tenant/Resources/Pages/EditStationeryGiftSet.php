<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryGiftSet\Pages;

use use App\Filament\Tenant\Resources\StationeryGiftSetResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStationeryGiftSet extends EditRecord
{
    protected static string $resource = StationeryGiftSetResource::class;

    public function getTitle(): string
    {
        return 'Edit StationeryGiftSet';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}