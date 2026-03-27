<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicLessonResource\Pages;

use App\Filament\Tenant\Resources\Music\MusicLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListMusicLessons page component.
 */
final class ListMusicLessons extends ListRecords
{
    protected static string $resource = MusicLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Lesson')
                ->icon('heroicon-o-plus'),
        ];
    }
}
