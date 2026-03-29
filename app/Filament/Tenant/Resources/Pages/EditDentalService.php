<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalService\Pages;

use use App\Filament\Tenant\Resources\DentalServiceResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditDentalService extends EditRecord
{
    protected static string $resource = DentalServiceResource::class;

    public function getTitle(): string
    {
        return 'Edit DentalService';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}