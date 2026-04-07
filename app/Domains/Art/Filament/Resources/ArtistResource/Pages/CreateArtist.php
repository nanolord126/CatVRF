<?php

declare(strict_types=1);

namespace App\Domains\Art\Filament\Resources\ArtistResource\Pages;

use App\Domains\Art\Filament\Resources\ArtistResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;
}
