<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReviewResource\Pages;

use App\Filament\Tenant\Resources\DentalReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalReview extends CreateRecord
{
    protected static string $resource = DentalReviewResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
