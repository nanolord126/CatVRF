<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources\ReturnPolicyResource\Pages;

use Modules\Supermarket\Filament\Resources\ReturnPolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnPolicy extends CreateRecord
{
    protected static string $resource = ReturnPolicyResource::class;
}
