<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReviewResource\Pages;

use App\Filament\Tenant\Resources\DentalReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListDentalReviews extends ListRecords
{
    protected static string $resource = DentalReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
