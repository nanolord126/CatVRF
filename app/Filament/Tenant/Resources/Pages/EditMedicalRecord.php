<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalRecord\Pages;

use use App\Filament\Tenant\Resources\MedicalRecordResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditMedicalRecord extends EditRecord
{
    protected static string $resource = MedicalRecordResource::class;

    public function getTitle(): string
    {
        return 'Edit MedicalRecord';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}