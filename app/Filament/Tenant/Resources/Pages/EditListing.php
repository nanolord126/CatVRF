<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Listing\Pages;

use use App\Filament\Tenant\Resources\ListingResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditListing extends EditRecord
{
    protected static string $resource = ListingResource::class;

    public function getTitle(): string
    {
        return 'Edit Listing';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}