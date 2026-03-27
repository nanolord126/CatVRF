<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReviewResource\Pages;

use App\Filament\Tenant\Resources\DentalReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditDentalReview extends EditRecord
{
    protected static string $resource = DentalReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
