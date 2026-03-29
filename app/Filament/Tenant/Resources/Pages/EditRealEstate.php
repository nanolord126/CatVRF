<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate\Pages;

use use App\Filament\Tenant\Resources\RealEstateResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditRealEstate extends EditRecord
{
    protected static string $resource = RealEstateResource::class;

    public function getTitle(): string
    {
        return 'Edit RealEstate';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}