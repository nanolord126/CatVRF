<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotel\Pages;

use use App\Filament\Tenant\Resources\HotelResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    public function getTitle(): string
    {
        return 'Edit Hotel';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}