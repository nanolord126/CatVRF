<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Property\Pages;

use use App\Filament\Tenant\Resources\PropertyResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    public function getTitle(): string
    {
        return 'Edit Property';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}