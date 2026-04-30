<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAdShorts extends ListRecords
{
    protected static string $resource = AdShortResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
