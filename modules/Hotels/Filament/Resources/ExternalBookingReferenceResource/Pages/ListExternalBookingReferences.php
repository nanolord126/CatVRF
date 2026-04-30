<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource;

final class ListExternalBookingReferences extends ListRecords
{
    protected static string $resource = ExternalBookingReferenceResource::class;
}
