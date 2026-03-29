<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalDoctor\Pages;

use use App\Filament\Tenant\Resources\MedicalDoctorResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedicalDoctor extends EditRecord
{
    protected static string $resource = MedicalDoctorResource::class;

    public function getTitle(): string
    {
        return 'Edit MedicalDoctor';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}