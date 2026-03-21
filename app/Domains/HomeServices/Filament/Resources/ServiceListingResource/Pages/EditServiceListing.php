<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditServiceListing extends EditRecord
{
    protected static string $resource = ServiceListingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
