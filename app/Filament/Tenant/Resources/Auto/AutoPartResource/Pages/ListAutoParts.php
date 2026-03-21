<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages;

use App\Filament\Tenant\Resources\Auto\AutoPartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAutoParts extends ListRecords
{
    protected static string $resource = AutoPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
