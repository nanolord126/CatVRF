<?php

declare(strict_types=1);

namespace App\Domains\Staff\Filament\Resources\StaffMemberResource\Pages;

use App\Domains\Staff\Filament\Resources\StaffMemberResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateStaffMember extends CreateRecord
{
    protected static string $resource = StaffMemberResource::class;
}
