<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\EventReviewResource\Pages;

use App\Domains\Tickets\Filament\Resources\EventReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateEventReview extends CreateRecord
{
    protected static string $resource = EventReviewResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
