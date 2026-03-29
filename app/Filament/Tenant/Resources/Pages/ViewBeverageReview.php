<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReview\Pages;

use use App\Filament\Tenant\Resources\BeverageReviewResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeverageReview extends ViewRecord
{
    protected static string $resource = BeverageReviewResource::class;

    public function getTitle(): string
    {
        return 'View BeverageReview';
    }
}