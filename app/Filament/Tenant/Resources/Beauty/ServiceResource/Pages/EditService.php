<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;

use App\Filament\Tenant\Resources\Beauty\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = \Illuminate\Support\Str::uuid()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
