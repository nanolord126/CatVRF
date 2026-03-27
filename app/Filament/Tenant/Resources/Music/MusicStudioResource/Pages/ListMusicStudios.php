<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicStudioResource\Pages;

use App\Filament\Tenant\Resources\Music\MusicStudioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListMusicStudios page component.
 */
final class ListMusicStudios extends ListRecords
{
    protected static string $resource = MusicStudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Studio')
                ->icon('heroicon-o-plus'),
        ];
    }
}
