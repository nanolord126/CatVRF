<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ListingResource\Pages;

use App\Filament\Tenant\Resources\ListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
