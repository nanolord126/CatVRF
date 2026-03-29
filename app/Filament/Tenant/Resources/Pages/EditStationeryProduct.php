<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryProduct\Pages;

use use App\Filament\Tenant\Resources\StationeryProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStationeryProduct extends EditRecord
{
    protected static string $resource = StationeryProductResource::class;

    public function getTitle(): string
    {
        return 'Edit StationeryProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}