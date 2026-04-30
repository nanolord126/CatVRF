<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListAuctions extends ListRecords
{
    protected static string $resource = AuctionResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
