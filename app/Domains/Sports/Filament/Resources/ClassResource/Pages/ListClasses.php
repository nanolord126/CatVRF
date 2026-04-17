<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\ClassResource\Pages;

use App\Domains\Sports\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListClasses extends ListRecords
{
    protected static string $resource = ClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
