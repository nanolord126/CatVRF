<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageReview\Pages;

use use App\Filament\Tenant\Resources\BeverageReviewResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeverageReview extends CreateRecord
{
    protected static string $resource = BeverageReviewResource::class;

    public function getTitle(): string
    {
        return 'Create BeverageReview';
    }
}