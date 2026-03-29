<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TravelTourism\Pages;

use use App\Filament\Tenant\Resources\TravelTourismResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditTravelTourism extends EditRecord
{
    protected static string $resource = TravelTourismResource::class;

    public function getTitle(): string
    {
        return 'Edit TravelTourism';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}