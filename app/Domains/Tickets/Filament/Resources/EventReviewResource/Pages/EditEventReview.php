<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources\EventReviewResource\Pages;

use App\Domains\Tickets\Filament\Resources\EventReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditEventReview extends EditRecord
{
    protected static string $resource = EventReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
