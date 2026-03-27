<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\ReviewResource\Pages;

use App\Filament\Tenant\Resources\Entertainment\ReviewResource;
use Filament\Resources\Pages\ListRecords;

final class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;
}
