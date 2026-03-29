<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use use App\Filament\Tenant\Resources\HotelsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditHotels extends EditRecord
{
    protected static string $resource = HotelsResource::class;

    public function getTitle(): string
    {
        return 'Edit Hotels';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}