<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages;

use App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNotificationExperiment extends CreateRecord
{
    protected static string $resource = NotificationExperimentResource::class;
}
