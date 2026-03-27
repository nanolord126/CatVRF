<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music\MusicReviewResource\Pages;

use App\Filament\Tenant\Resources\Music\MusicReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListMusicReviews page component.
 */
final class ListMusicReviews extends ListRecords
{
    protected static string $resource = MusicReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Review Manually')
                ->icon('heroicon-o-plus'),
        ];
    }
}
