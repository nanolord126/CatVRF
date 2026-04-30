<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\PublisherResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\PublisherResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListPublishers extends ListRecords
{
    protected static string $resource = PublisherResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
