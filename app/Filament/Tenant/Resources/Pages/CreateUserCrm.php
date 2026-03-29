<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrm\Pages;

use use App\Filament\Tenant\Resources\UserCrmResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateUserCrm extends CreateRecord
{
    protected static string $resource = UserCrmResource::class;

    public function getTitle(): string
    {
        return 'Create UserCrm';
    }
}