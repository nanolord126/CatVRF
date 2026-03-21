<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Filament\Resources\EntertainerResource\Pages;

use App\Domains\Entertainment\Filament\Resources\EntertainerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListEntertainers extends ListRecords
{
    protected static string $resource = EntertainerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
