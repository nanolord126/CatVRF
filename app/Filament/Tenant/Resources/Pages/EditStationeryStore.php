<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryStore\Pages;

use use App\Filament\Tenant\Resources\StationeryStoreResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStationeryStore extends EditRecord
{
    protected static string $resource = StationeryStoreResource::class;

    public function getTitle(): string
    {
        return 'Edit StationeryStore';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}