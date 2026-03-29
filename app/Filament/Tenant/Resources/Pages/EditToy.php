<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Toy\Pages;

use use App\Filament\Tenant\Resources\ToyResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditToy extends EditRecord
{
    protected static string $resource = ToyResource::class;

    public function getTitle(): string
    {
        return 'Edit Toy';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}