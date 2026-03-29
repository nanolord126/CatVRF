<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Dentist\Pages;

use use App\Filament\Tenant\Resources\DentistResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDentist extends EditRecord
{
    protected static string $resource = DentistResource::class;

    public function getTitle(): string
    {
        return 'Edit Dentist';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}