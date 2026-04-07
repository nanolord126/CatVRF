<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerProductResource\Pages;

use App\Filament\Tenant\Resources\Flowers\FlowerProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFlowerProducts extends ListRecords
{
    protected static string $resource = FlowerProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus-circle'),
        ];
    }
}
