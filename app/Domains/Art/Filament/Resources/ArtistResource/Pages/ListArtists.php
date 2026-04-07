<?php

declare(strict_types=1);

namespace App\Domains\Art\Filament\Resources\ArtistResource\Pages;

use App\Domains\Art\Filament\Resources\ArtistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListArtists extends ListRecords
{
    protected static string $resource = ArtistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
