<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\AuctionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditAuction extends EditRecord
{
    protected static string $resource = AuctionResource::class;
}
