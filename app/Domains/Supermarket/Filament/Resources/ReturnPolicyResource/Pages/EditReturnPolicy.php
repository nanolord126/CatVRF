<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Filament\Resources\ReturnPolicyResource\Pages;

use App\Domains\Supermarket\Filament\Resources\ReturnPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnPolicy extends EditRecord
{
    protected static string $resource = ReturnPolicyResource::class;

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
