<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToysKids\Pages;

use use App\Filament\Tenant\Resources\ToysKidsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditToysKids extends EditRecord
{
    protected static string $resource = ToysKidsResource::class;

    public function getTitle(): string
    {
        return 'Edit ToysKids';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}