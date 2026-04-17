<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources\B2BPetStorefrontResource\Pages;

use App\Domains\Pet\Filament\Resources\B2BPetStorefrontResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListB2BPetStorefronts extends ListRecords
{
    protected static string $resource = B2BPetStorefrontResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
