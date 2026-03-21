<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PharmacyResource\Pages;

use App\Filament\Tenant\Resources\PharmacyResource;
use Filament\Resources\Pages\EditRecord;

final class EditPharmacy extends EditRecord
{
    protected static string $resource = PharmacyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
