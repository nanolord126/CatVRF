<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PharmacyOrder\Pages;

use use App\Filament\Tenant\Resources\PharmacyOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditPharmacyOrder extends EditRecord
{
    protected static string $resource = PharmacyOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit PharmacyOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}