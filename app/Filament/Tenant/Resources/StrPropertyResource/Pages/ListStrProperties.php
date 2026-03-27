<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrPropertyResource\Pages;

use App\Filament\Tenant\Resources\StrPropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStrProperties extends ListRecords
{
    protected static string $resource = StrPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
