<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels\Pages;

use App\Filament\Tenant\Resources\Channels\ChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditChannel extends EditRecord
{
    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
