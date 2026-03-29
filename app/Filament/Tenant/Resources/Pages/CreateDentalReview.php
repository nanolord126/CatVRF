<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReview\Pages;

use use App\Filament\Tenant\Resources\DentalReviewResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateDentalReview extends CreateRecord
{
    protected static string $resource = DentalReviewResource::class;

    public function getTitle(): string
    {
        return 'Create DentalReview';
    }
}