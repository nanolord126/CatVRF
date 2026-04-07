<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Filament\Resources\AnalyticsEventResource\Pages;

use App\Domains\Analytics\Filament\Resources\AnalyticsEventResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAnalyticsEvent extends CreateRecord
{
    protected static string $resource = AnalyticsEventResource::class;
}
