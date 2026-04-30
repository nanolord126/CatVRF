<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource;

final class CreateExternalBookingReference extends CreateRecord
{
    protected static string $resource = ExternalBookingReferenceResource::class;
}
