<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalConsumable\Pages;

use use App\Filament\Tenant\Resources\DentalConsumableResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDentalConsumable extends EditRecord
{
    protected static string $resource = DentalConsumableResource::class;

    public function getTitle(): string
    {
        return 'Edit DentalConsumable';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}