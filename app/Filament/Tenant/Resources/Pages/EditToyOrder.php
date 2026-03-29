<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrder\Pages;

use use App\Filament\Tenant\Resources\ToyOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditToyOrder extends EditRecord
{
    protected static string $resource = ToyOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit ToyOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}