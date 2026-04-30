<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource;

final class ViewExternalBookingReference extends ViewRecord
{
    protected static string $resource = ExternalBookingReferenceResource::class;
}
