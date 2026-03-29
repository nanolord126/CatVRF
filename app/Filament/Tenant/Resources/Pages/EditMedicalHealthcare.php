<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalHealthcare\Pages;

use use App\Filament\Tenant\Resources\MedicalHealthcareResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedicalHealthcare extends EditRecord
{
    protected static string $resource = MedicalHealthcareResource::class;

    public function getTitle(): string
    {
        return 'Edit MedicalHealthcare';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}