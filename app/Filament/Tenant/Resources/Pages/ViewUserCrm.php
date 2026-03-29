<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrm\Pages;

use use App\Filament\Tenant\Resources\UserCrmResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewUserCrm extends ViewRecord
{
    protected static string $resource = UserCrmResource::class;

    public function getTitle(): string
    {
        return 'View UserCrm';
    }
}