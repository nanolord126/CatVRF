<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditMaster extends EditRecord
{
    protected static string $resource = MasterResource::class;

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
