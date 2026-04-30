<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnResource\Pages;

use App\Domains\Supermarket\Filament\Resources\ReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * EditReturn - Страница редактирования возврата.
 */
class EditReturn extends EditRecord
{
    protected static string $resource = ReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
