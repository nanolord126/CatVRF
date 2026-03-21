<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources\ServiceReviewResource\Pages;

use App\Domains\HomeServices\Filament\Resources\ServiceReviewResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateServiceReview extends CreateRecord
{
    protected static string $resource = ServiceReviewResource::class;
}
