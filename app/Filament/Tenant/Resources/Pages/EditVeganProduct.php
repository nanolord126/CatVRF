<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VeganProduct\Pages;

use use App\Filament\Tenant\Resources\VeganProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditVeganProduct extends EditRecord
{
    protected static string $resource = VeganProductResource::class;

    public function getTitle(): string
    {
        return 'Edit VeganProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}