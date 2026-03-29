<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pharmacy\Pages;

use use App\Filament\Tenant\Resources\PharmacyResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPharmacy extends EditRecord
{
    protected static string $resource = PharmacyResource::class;

    public function getTitle(): string
    {
        return 'Edit Pharmacy';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}