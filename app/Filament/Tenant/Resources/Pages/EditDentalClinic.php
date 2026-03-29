<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinic\Pages;

use use App\Filament\Tenant\Resources\DentalClinicResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDentalClinic extends EditRecord
{
    protected static string $resource = DentalClinicResource::class;

    public function getTitle(): string
    {
        return 'Edit DentalClinic';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}