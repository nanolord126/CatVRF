<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceReviewResource;
use Filament\Resources\Pages\ListRecords;

final class ListServiceReviews extends ListRecords
{
    protected static string $resource = ServiceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
