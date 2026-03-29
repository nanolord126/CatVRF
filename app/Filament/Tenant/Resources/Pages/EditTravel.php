<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Travel\Pages;

use use App\Filament\Tenant\Resources\TravelResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditTravel extends EditRecord
{
    protected static string $resource = TravelResource::class;

    public function getTitle(): string
    {
        return 'Edit Travel';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}