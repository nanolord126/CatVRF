<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsProduct\Pages;

use use App\Filament\Tenant\Resources\KidsProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditKidsProduct extends EditRecord
{
    protected static string $resource = KidsProductResource::class;

    public function getTitle(): string
    {
        return 'Edit KidsProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}