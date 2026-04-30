<?php

declare(strict_types=1);

namespace App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource\Pages;

use App\Domains\Shared\CRM\Filament\Resources\NotificationExperimentResource;
use Filament\Resources\Pages\EditRecord;

final class EditNotificationExperiment extends EditRecord
{
    protected static string $resource = NotificationExperimentResource::class;
}
