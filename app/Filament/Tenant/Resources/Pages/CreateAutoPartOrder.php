<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartOrder\Pages;

use use App\Filament\Tenant\Resources\AutoPartOrderResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateAutoPartOrder extends CreateRecord
{
    protected static string $resource = AutoPartOrderResource::class;

    public function getTitle(): string
    {
        return 'Create AutoPartOrder';
    }
}