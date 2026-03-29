<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CollectibleAuction\Pages;

use use App\Filament\Tenant\Resources\CollectibleAuctionResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCollectibleAuction extends EditRecord
{
    protected static string $resource = CollectibleAuctionResource::class;

    public function getTitle(): string
    {
        return 'Edit CollectibleAuction';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}