<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalReview\Pages;

use use App\Filament\Tenant\Resources\DentalReviewResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewDentalReview extends ViewRecord
{
    protected static string $resource = DentalReviewResource::class;

    public function getTitle(): string
    {
        return 'View DentalReview';
    }
}