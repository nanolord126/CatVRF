<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\ReviewResource\Pages;

use App\Domains\Sports\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

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
