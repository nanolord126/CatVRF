<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource;

final class EditExternalBookingReference extends EditRecord
{
    protected static string $resource = ExternalBookingReferenceResource::class;
}
