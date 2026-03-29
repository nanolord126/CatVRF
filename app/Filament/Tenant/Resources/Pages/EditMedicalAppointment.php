<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalAppointment\Pages;

use use App\Filament\Tenant\Resources\MedicalAppointmentResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedicalAppointment extends EditRecord
{
    protected static string $resource = MedicalAppointmentResource::class;

    public function getTitle(): string
    {
        return 'Edit MedicalAppointment';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}