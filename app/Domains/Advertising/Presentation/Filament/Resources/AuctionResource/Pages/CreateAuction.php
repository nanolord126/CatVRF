<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateAuction extends CreateRecord
{
    protected static string $resource = AuctionResource::class;
}
