<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalClinic\Pages;

use use App\Filament\Tenant\Resources\MedicalClinicResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedicalClinic extends EditRecord
{
    protected static string $resource = MedicalClinicResource::class;

    public function getTitle(): string
    {
        return 'Edit MedicalClinic';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}