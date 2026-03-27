<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceServiceOfferResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceServiceOfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListFreelanceServiceOffers extends ListRecords
{
    protected static string $resource = FreelanceServiceOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
