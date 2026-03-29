<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalSupplies\Pages;

use use App\Filament\Tenant\Resources\MedicalSuppliesResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedicalSupplies extends EditRecord
{
    protected static string $resource = MedicalSuppliesResource::class;

    public function getTitle(): string
    {
        return 'Edit MedicalSupplies';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}