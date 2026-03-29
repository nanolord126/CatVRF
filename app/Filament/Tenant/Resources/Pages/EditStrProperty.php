<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrProperty\Pages;

use use App\Filament\Tenant\Resources\StrPropertyResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditStrProperty extends EditRecord
{
    protected static string $resource = StrPropertyResource::class;

    public function getTitle(): string
    {
        return 'Edit StrProperty';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}