<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\StudioResource\Pages;

use App\Domains\Sports\Filament\Resources\StudioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListStudios extends ListRecords
{
    protected static string $resource = StudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
