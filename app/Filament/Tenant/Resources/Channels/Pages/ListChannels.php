<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels\Pages;

use App\Filament\Tenant\Resources\Channels\ChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListChannels extends ListRecords
{
    protected static string $resource = ChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Создать канал'),
        ];
    }
}
