<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Sports\Pages;

use use App\Filament\Tenant\Resources\SportsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditSports extends EditRecord
{
    protected static string $resource = SportsResource::class;

    public function getTitle(): string
    {
        return 'Edit Sports';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}