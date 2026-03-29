<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Master\Pages;

use use App\Filament\Tenant\Resources\MasterResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;

    public function getTitle(): string
    {
        return 'Create Master';
    }
}