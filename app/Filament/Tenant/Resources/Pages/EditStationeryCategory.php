<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryCategory\Pages;

use use App\Filament\Tenant\Resources\StationeryCategoryResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStationeryCategory extends EditRecord
{
    protected static string $resource = StationeryCategoryResource::class;

    public function getTitle(): string
    {
        return 'Edit StationeryCategory';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}