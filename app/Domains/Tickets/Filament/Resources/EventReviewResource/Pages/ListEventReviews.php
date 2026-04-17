<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\EventReviewResource\Pages;

use App\Domains\Tickets\Filament\Resources\EventReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListEventReviews extends ListRecords
{
    protected static string $resource = EventReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
